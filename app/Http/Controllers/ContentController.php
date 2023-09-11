<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public $client;
    public $solr_url;
    public $query;

    public function __construct()
    {
        $this->client = new GuzzleClient([
            'connect_timeout' => 86400.0,
            'timeout' => 86400.0,
            'verify' => false,
            'cookies' => true
        ]);
    
        $this->solr_url = env('SOLR_URL');
        $this->query = "/select?q=system_name:pkw AND table_name:records AND publisher:(Youtube or creativecontent) ";
    }

    public function search()
    {
        $page = request('page') && request('page') != "null" ? intval(request('page')) - 1 : 0;
        $length = request('length') && request('length') != "null"? intval(request('length'))  : 10;
        $query = $this->query;
        $q_vall = "";
        if("null" !==request('q')){
            $query .= ' AND title:' . request('q') .' OR description:'. request('q');
            $q_vall .= "q=" . request('q');
        }
        if("null" !==request('sub-class')){
            $subject_sub_class = request('sub-class');
            if(request('sub-class') == '410'){
                $subject_sub_class = "[4100 TO 4190]";
            }
            $query .= " AND subject_sub_class:" . $subject_sub_class . "&sort=field('prob_subject_sub_class')+desc";
            
            $q_vall .= "&sub-class=" . request('sub-class');
        }
        if("null" !==request('page')){
            $q_vall .= "&page=" . request('page');
        }
        if("null" !==request('length')){
            $q_vall .= "&length=" . request('length');
        }

        $query .= "&start=" . $page*$length . "&rows=$length";
        $response = $this->client->get($this->solr_url. $query);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true)["response"];
        $docs = $content["docs"];
        $numFound = $content["numFound"];
        $res = [];
        foreach($docs as $doc){
            $type = "";
            if(!isset($doc['type']) && (strpos(strtolower($doc['download_original']), "youtube") !== false)){
                $type = "video";
            } else if(strpos($doc['download_original'], "journal") !== false) {
                $type = "article";
            } else {
                $type = $doc['type'][0];
            }
            array_push($res,[
                'id' => $doc['id'],
                'title' => $doc['title'][0],
                'creator' => isset($doc['creator_string']) ? $doc['creator_string'][0] : '',
                'description' => isset($doc['description']) ? $doc['description'][0] : '',
                'type' => $type, //isset($doc['type'])? $doc['type'][0] : 'article',
                'link' => isset($doc['download_original']) ? $doc['download_original'] : '',
                'subject_class' => isset($doc['subject_class']) ? $doc['subject_class'][0] : '',
                'prob_subject_class' => isset($doc['prob_subject_class']) ? $doc['prob_subject_class'][0] : '',
                'subject_sub_class' => isset($doc['subject_sub_class']) ? $doc['subject_sub_class'][0] : '',
                'prob_subject_sub_class' => isset($doc['prob_subject_sub_class']) ? $doc['prob_subject_sub_class'][0] : '',
                'created_at' => isset($doc['created_at']) ? substr($doc['created_at'], 0, 10) : '',
            ]);
        }
        return response()->json(
            [
                "query" => $q_vall,
                "total_results" => $numFound,
                "length" => $length,
                "rows" => count($docs),
                "page" => request('page') == 'null' ? 1 : request('page'),
                "results" => $res,
                
            ]
        );
    }

    public function detail($id)
    {        
        $query = "/select?q=id:" . $id;
        $response = $this->client->get($this->solr_url. $query);
        $content = $response->getBody()->getContents();
        $doc = json_decode($content, true)["response"]["docs"][0];
        if(!isset($doc['type']) && (strpos(strtolower($doc['download_original']), "youtube") !== false)){
            $type = "video";
        } else if(strpos($doc['download_original'], "journal") !== false) {
            $type = "article";
        } else {
            $type = $doc['type'][0];
        }
        return response()->json(
            [
                'id' => $doc['id'],
                'title' => $doc['title'][0],
                'description' => isset($doc['description']) ? $doc['description'][0] : '',
                'type' => $type,
                'link' => isset($doc['download_original']) ? $doc['download_original'] : '',
                'subject_class' => isset($doc['subject_class']) ? $doc['subject_class'][0] : '',
                'prob_subject_class' => isset($doc['prob_subject_class']) ? $doc['prob_subject_class'][0] : '',
                'subject_sub_class' => isset($doc['subject_sub_class']) ? $doc['subject_sub_class'][0] : '',
                'prob_subject_sub_class' => isset($doc['prob_subject_sub_class']) ? $doc['prob_subject_sub_class'][0] : '',
                'subject' => isset($doc['subject_string']) ? $doc['subject_string'] : '',
                'year'=> isset($doc['year_string']) ? $doc['year_string'][0] : '',
                'creator' => isset($doc['creator']) ? $doc['creator'] : [],
                'contributor' => isset($doc['contributor']) ? $doc['contributor'] : [],
                'created_at' => isset($doc['created_at']) ? $doc['created_at'] : '',
            ]
        );
    }
    function process_csv($file) {

        $file = fopen($file, "r");
        $data = array();
    
        while (!feof($file)) {
            $data[] = fgetcsv($file, null, ';');
        }
    
        fclose($file);
        return $data;
    }
    public function getSubClassDesc($sub_class)
    {
        $data = $this->process_csv('klasifikasi-ddc.csv');
        $class = substr($sub_class, 0 ,1) . "xx";
        \Log::info($class);
        $nama_class = "";
        foreach($data as $d){
            if(strtolower($d[0]) == $class){
                $nama_class = $d[1];
            }
            if($d[0] == $sub_class){
                return 
                response()->json([ $nama_class , $d[1]]);
            }
       }
    }
}
