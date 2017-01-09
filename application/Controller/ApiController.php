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
 * @file ApiController.php
 * @author Edison Ong
 * @since Oct 23, 2015
 * @comment 
 */

namespace Controller;

use Exception;

use Controller\Controller;

use Model\OntologyModel;

class ApiController extends Controller  {
	public function search( $params = array() ) {
		$GLOBALS['show_query'] = false;
		error_reporting(0);
		
		$this->loadModel( 'Ontology' );

		list( $ontAbbr, $termIRI ) = $this->parseOntologyParameter( $params );

		$keyword = null;
		if ( array_key_exists( 'term' , $params ) ) {
			$keyword = $params['term'];
		} else if ( array_key_exists( 'keyword' , $params ) ) {
			$keyword = $params['keyword'];
		} else if ( array_key_exists( 'keywords' , $params ) ) {
			$keyword = $params['keywords'];
		} else if ( !empty ( $params ) ) {
			$keyword = array_shift( $params );
		}
		if ( !is_null( $keyword ) ) {
			$json = $this->model->searchKeyword( $keyword, $ontAbbr, 50 );
		} else {
			throw new Exception( "Excess parameters." );
		}
		$resultQueue = array();
		foreach ( $json as $index => $result ) {
			$resultString = join( '-', $result );
			if ( in_array( $resultString, $resultQueue ) ) {
				unset( $json[$index] );
			} else {
				$resultQueue[] = $resultString;
			}
		}
		echo json_encode( $json );
	}
	
	public function infobox( $params = array() ) {
		$GLOBALS['show_query'] = false;
		list( $ontAbbr, $termIRI ) = $this->parseOntologyParameter( $params );
		$this->loadModel( 'Ontology' );
		$this->model->loadOntology( $ontAbbr, $termIRI, null, false );
		echo json_encode( $this->model->describeTerm( $termIRI ) );
	}
}


?>