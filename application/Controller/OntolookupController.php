<?php

/**
 * Copyright © 2015 The Regents of the University of Michigan
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 * 
 * For more information, questions, or permission requests, please contact:
 * Yongqun “Oliver” He - yongqunh@med.umich.edu
 * Unit for Laboratory Animal Medicine, Center for Computational Medicine & Bioinformatics
 * University of Michigan, Ann Arbor, MI 48109, USA
 * He Group:  http://www.hegroup.org
 */

/**
 * @file OntolookupController.php
 * @author Edison Ong
 * @since Jan 3, 2018
 * @comment 
 */

namespace Controller;

use Exception;

use Controller\Controller;

use Model\OntologyModel;

class OntolookupController extends Controller  {
    public function index( $params = array() ) {
        $GLOBALS['show_query'] = false;
        
        $this->loadModel( 'Ontology' );
        
        list( $ontAbbr, $termIRI ) = $this->parseOntologyParameter( $params );
        
        $this->model->loadOntology( $ontAbbr, $termIRI, null, false );
        $ontology = $this->model->getOntology();
        
        $this->model->loadClass( $termIRI );
        $term = $this->model->getTerm();
        
        $graph = urlencode( 'http://purl.obolibrary.org/obo/merged/OAE' );
        $root = 'http://purl.obolibrary.org/obo/OAE_0000001';
        list( $map, $mapLabel ) = $this->queryTreeJSON( $graph, $root );
        $treeMaker = new MyTreeHelper( $map, $mapLabel );
        $treeMaker->parseTree( $root );
        $tree = $treeMaker->getTree();
        
        require VIEWPATH . 'Ontolookup/index.php';
    }
    
    private function queryTreeJSON( $graph, $root ) {
        $json = file_get_contents( "http://sparql.hegroup.org/sparql/?default-graph-uri=&query=PREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0APREFIX+rdfs%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F2000%2F01%2Frdf-schema%23%3E%0D%0APREFIX+owl%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F2002%2F07%2Fowl%23%3E%0D%0ASELECT+DISTINCT+%3Fo+%3Fl1+%3Fp+%3Fl2+WHERE+%7B%0D%0A%09%7B%0D%0A%09%09SELECT+DISTINCT+%3Fo+FROM+%3C{$graph}%3E+WHERE+%7B%0D%0A%09%09%09%7B%0D%0A%09%09%09%09%7B%0D%0A%09%09%09%09%09SELECT+%3Fs+%3Fo+WHERE+%7B%0D%0A%09%09%09%09%09%09%7B%0D%0A%09%09%09%09%09%09%09%3Fo+rdfs%3AsubClassOf+%3Fs+.%0D%0A%09%09%09%09%09%09%09FILTER+%28+isIRI%28+%3Fo+%29+%29+.%0D%0A%09%09%09%09%09%09%7D%0D%0A%09%09%09%09%09%7D%0D%0A%09%09%09%09%7D%0D%0A%09%09%09%09OPTION+%28TRANSITIVE%2C+t_in%28%3Fs%29%2C+t_out%28%3Fo%29%2C+t_step+%28%3Fs%29+as+%3Flink%2C+t_step+%28%27path_id%27%29+as+%3Fpath%29.%0D%0A%09%09%09%09FILTER+%28+isIRI%28+%3Fo+%29+%29.%0D%0A%09%09%09%09FILTER+%28+%3Fs+%3D+%3C{$root}%3E+%29%0D%0A%09%09%09%7D%0D%0A%09%09%7D%0D%0A%09%7D+.%0D%0A%09%3Fo+rdfs%3Alabel+%3Fl1+.%0D%0A%09%3Fo+rdfs%3AsubClassOf+%3Fp+.%0D%0A%09%3Fp+rdfs%3Alabel+%3Fl2+.%0D%0A%09FILTER+%28+isIRI%28+%3Fp+%29+%29%0D%0A%7D&format=application%2Fsparql-results%2Bjson&debug=on&timeout=" );
        $json = json_decode( $json, true );
        $map = array();
        $mapLabel = array();
        foreach( $json['results']['bindings'] as $value ) {
            $child = $value['o']['value'];
            $parent = $value['p']['value'];
            if ( !isset( $map[ $parent ] ) ) {
                $map[$parent] = array();
                $map[$parent][] = $child;
                
            } else if ( !in_array( $child, $map[$parent] ) ) {
                $map[$parent][] = $child;
            }
            if ( !isset( $map_label[$child] ) ) {
                $mapLabel[$child] = $value['l1']['value'];
            }
            if ( !isset( $map_label[$parent] ) ) {
                $mapLabel[$parent] = $value['l2']['value'];
            }
        }
        #print_r($map);
        #print_r($mapLabel);
        return array( $map, $mapLabel );
    }
    
}

class MyTreeHelper {
	private $map;
	private $mapLabel;
	private $tree;

	public function __construct( $map, $mapLabel ) {
		$this->map = $map;
		$this->mapLabel = $mapLabel;
	}

	public function getTree() {
		return $this->tree;
	}

	private function parseTreeRecursive( $parent ) {
		$map = $this->map;
		$mapLabel = $this->mapLabel;
		$result = array();
		if ( isset( $map[$parent] ) ) {
			foreach( $map[$parent] as $child ) {
				$result[] = array(
						'iri' => $child,
						'label' => $mapLabel[$child],
						'children' => $this->parseTreeRecursive( $child ),
				);
			}
		}
		return $result;
	}

	public function parseTree( $root ) {
		$this->tree[] = array(
				'iri' => $root,
				'label' => $this->mapLabel[$root],
				'children' => $this->parseTreeRecursive( $root ),
		);
	}
}

?>