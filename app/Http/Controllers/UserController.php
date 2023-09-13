<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;

class UserController extends Controller
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
        $this->query = "/select?q=system_name:pkw AND table_name:records ";
    }

    public function detail()
    {
        $query = $this->query;
        $q_vall = "";
        if("null" !== request('q')){
            $query .= ' AND creator:"' .request('q').'"  AND publisher:Youtube';
            $q_vall .= "q=" . request('q');
        }

        $response = $this->client->get($this->solr_url. $query);
        $result = $response->getBody()->getContents();
        $numFound_video = json_decode($result, true)["response"]["numFound"];
        
        $query2 = $this->query;
        if("null" !== request('q')){
            $query2 .= ' AND creator:"' .request('q').'" AND publisher:creativecontent';
        }

        $response2 = $this->client->get($this->solr_url. $query2);
        $result2 = $response2->getBody()->getContents();
        $numFound_artikel = json_decode($result2, true)["response"]["numFound"];


        return response()->json(
            [   
                "total_konten" => intval($numFound_video) + intval($numFound_artikel),
                "video" => $numFound_video,
                "artikel" => $numFound_artikel
            ]
        );
    }

}
