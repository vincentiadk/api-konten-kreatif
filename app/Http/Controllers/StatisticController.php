<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;

class StatisticController extends Controller
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

    public function getClass()
    {
        $query = "$this->query&facet=true&facet.field=subject_class&facet.mincount=1&facet.sort=index&rows=0&json.nl=arrmap";
        $response = $this->client->get($this->solr_url. $query);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true)["facet_counts"]["facet_fields"]["subject_class"];

        $content0 = [];
        foreach($content as $cont){
            $key = key($cont);
            $content0 = array_merge($content0,[
                $key => $cont[$key]
            ]);
        }
        $content = $content0;
        return response()->json($content);
    }

    public function getSubClass($class)
    {        
        $query = "$this->query AND subject_class:$class&facet=true&facet.field=subject_sub_class&facet.mincount=1&facet.sort=index&rows=0&json.nl=arrmap" ;
        $response = $this->client->get($this->solr_url. $query);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true)["facet_counts"]["facet_fields"]["subject_sub_class"];
        
        $content0 = [];       
        foreach($content as $cont){
            $key = key($cont);
            $key_ = substr($class, 0, 1) . substr(key($cont), 0, 2); 
            $val = $cont[$key];

            if(array_key_exists($key_, $content0)){
                $content0[$key_] = $content0[$key_] + $val;
            } else {
                $content0 = array_merge($content0,[
                    $key_ => $val
                ]);
            }
        }
        $content = $content0;
        return response()->json($content);
    }

    public function getUnit()
    {
        $query = "$this->query&facet=true&facet.field=volume&facet.mincount=1&facet.sort=index&rows=0&json.nl=arrmap";
        $response = $this->client->get($this->solr_url. $query);
        $content = $response->getBody()->getContents();
        $content = json_decode($content, true)["facet_counts"]["facet_fields"]["volume"];

        $content0 = [];
        foreach($content as $cont){
            $key = key($cont);
            $content0 = array_merge($content0,[
                $key => $cont[$key]
            ]);
        }
        $content = $content0;
        return response()->json($content);
    }

    public function getDate(Request $request)
    {
        $query = "$this->query&json.nl=map&indent=true&rows=0&facet=true&facet.range=created_at&facet.mincount=1";
        if(isset($request->date_type)){
            if($request->date_type == 'harian'){
                $query .= "&facet.range.gap=%2B1DAY";
            } else if($request->date_type == 'bulanan'){
                $query .= "&facet.range.gap=%2B1MONTH";
            } else if($request->date_type == 'tahunan'){
                $query .= "&facet.range.gap=%2B1YEAR";
            }
        } else {
            $query .= "&facet.range.gap=%2B1MONTH";
        }
        if(isset($request->date_start)) {
            $query .= "&facet.range.start=" . date_format(date_create($request->date_start .' 00:00:00'), 'Y-m-d\TH:i:s\Z');
        } else {
            $query .= "&facet.range.start=2022-06-01T00:00:00Z";
        }
        if(isset($request->date_end)) {
            $query .= "&facet.range.end=" . date_format(date_create($request->date_end . ' 23:59:59'), 'Y-m-d\TH:i:s\Z');
        } else {
            $query .= "&facet.range.end=" . now()->format('Y-m-d\TH:i:s\Z');
        }
        $response = $this->client->get($this->solr_url. $query);
        $content = $response->getBody()->getContents();

		$content = json_decode($content,true)["facet_counts"]["facet_ranges"]["created_at"]["counts"];
		$row = [];
		foreach($content as $key => $val){
            $date_view = substr($key, 0, 7);
            if(isset($request->date_type)) {
                if($request->date_type == 'harian'){
                    $date_view = substr($key, 0, 10);
                } else if($request->date_type == 'bulanan'){
                    $date_view = substr($key, 0, 7);
                } else if($request->date_type == 'tahunan'){
                    $date_view = substr($key, 0, 4);
                } 
            }
			$row[] = [
				"key" => $date_view,
				"val" => $val
			];
		}
		return $row;
    }

}
