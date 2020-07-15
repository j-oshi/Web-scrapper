<?php

class scrapper
{
    private $url = '';
    private $initialResultUrl = '';
    private $resultNode = [];

    public function __construct($url)
    {
        $this->url = $url;
        $this->initialResultUrl = file_get_contents("$url");
    }

    /*
    * Get result header title
    * return array
    */
    private function getResultHeader()
    {
        if (isset($this->url)) {
            $dom = new DOMDocument();
            $page_content = file_get_contents($this->url);
            @$dom->loadHTML($page_content);

            $els = $dom->getElementById($id);

            // $header = $els->childNodes->getAttribute('id');
            $child_nodes = $els->getElementsByTagName('th');

            $header_values = [];
            foreach ($child_nodes as $entry) {
                if (!empty($entry->nodeValue)) {
                    $header_values[] = $entry->nodeValue;
                }
            }

            return $header_values;
        }
    }

    /*
    * Get page result
    * return array
    */
    public function getChildNodeOfResult()
    {
        $furtherPageResult = $this->checkFurtherResultUrl();
        // Reset
        $this->resultNode = [];
        $url = $this->url;
        if (count($furtherPageResult) > 0 && isset($url)) {
            foreach ($furtherPageResult as $query) {
                $this->pageResult($url . $query);
            }
        } else {
            $this->pageResult($url);
        }
        $this->saveToCSV();
        echo 'Please check folder for csv file';
    }


    private function pageResult($url)
    {
        if (isset($url)) {
            $dom = new DOMDocument();
            $page_content = file_get_contents("$url");
            @$dom->loadHTML($page_content);

            $els = $dom->getElementsByTagName('tbody');

            $rows = $els->item(0);
            $results = [];
            $body_values = [];
            foreach ($rows->childNodes as $nodename) {
                if (isset($nodename->tagName) && !empty($nodename->getElementsByTagName("span")->item(0)->nodeValue)) {
                    $body_values['title'] = trim($nodename->getElementsByTagName("span")->item(0)->nodeValue);
                    $body_values['location'] = trim($nodename->getElementsByTagName("span")->item(2)->nodeValue);
                    $body_values['department'] = trim($nodename->getElementsByTagName("span")->item(5)->nodeValue);
                    $body_values['operation'] = trim($nodename->getElementsByTagName("span")->item(6)->nodeValue);
                    array_push($results, $body_values);
                }
            }
            array_push($this->resultNode, $results);
        }
    }

    /*
    * Get query for further result
    * return array
    */
    private function checkFurtherResultUrl()
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($this->initialResultUrl);

        $xpath = new DOMXPath($dom);
        $pagination = $xpath->query('//ul[@class="pagination"]//li/a/@href');

        if (isset($pagination)) {
            $remove_duplicate = [];
            foreach ($pagination as $node) {
                $remove_duplicate[] = $node->nodeValue;
            }
            $result = array_unique($remove_duplicate);

            return $result;
        }
    }

    private function saveToCSV()
    {
        if (isset($this->resultNode)) {
            foreach ($this->resultNode as $nodename) {
            $header = false;
            $fp = fopen($_SERVER["DOCUMENT_ROOT"]."/web-scrapper/result.csv", "w+");
            $resultValues = $nodename;
            foreach ($resultValues as $row) {
                if (!$header) {
                    fputcsv($fp, array_keys($row));
                    $header=true;
                }
                fputcsv($fp, $row);
            }
            fclose($fp);
            }
        }
    }

    // Return node array
    public function Result()
    {
        if (isset($this->resultNode)) {
            return $this->resultNode;
        }
    }
}