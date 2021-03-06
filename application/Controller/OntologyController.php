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
 * @file OntologyController.php
 * @author Edison Ong
 * @since Sep 3, 2015
 * @comment 
 */
 
namespace Controller;

use \Exception;
use Controller\Controller;

use Controller\ErrorController;
use Model\OntologyModel;

Class OntologyController extends Controller{	
	public function index( $params = array() ) {
		list( $ontAbbr, $termIRI ) = $this->parseOntologyParameter( $params );
		if ( is_null( $termIRI ) ) {
			$this->view( $params );
		} else {
			$GLOBALS['show_query'] = false;
			$xslt = true;
			$this->loadModel( 'Ontology' );
			$this->model->loadOntology( $ontAbbr, $termIRI, null, false );
			$title = "Ontobee: $ontAbbr";
			$ontology = $this->model->getOntology();
			$type = $this->model->askTermType( $termIRI );
			if ( empty( $ontology ) ) {
				$error = new ErrorController();
				$error->index( ErrorController::ONTOLOGY_NOT_FOUND );
			} else if ( !$type ) {
				$error = new ErrorController();
				$error->index( ErrorController::TERM_NOT_FOUND );
			} else {
				$this->model->loadRDF( $termIRI );
				$rdf = $this->model->getRDF();
				require VIEWPATH . 'Ontology/rdf.php';
			}
		}
	}
	
	public function rdf( $params = array() ) {
		$GLOBALS['show_query'] = false;
		$xslt = false;
		list( $ontAbbr, $termIRI ) = $this->parseOntologyParameter( $params );
		$this->loadModel( 'Ontology' );
		$this->model->loadOntology( $ontAbbr, $termIRI, null, false );
		$title = "Ontobee: $ontAbbr";
		$ontology = $this->model->getOntology();
		$type = $this->model->askTermType( $termIRI );
		if ( empty( $ontology ) ) {
			$error = new ErrorController();
			$error->index( ErrorController::ONTOLOGY_NOT_FOUND );
		} else if ( !$type ) {
			$error = new ErrorController();
			$error->index( ErrorController::TERM_NOT_FOUND );
		} else {
			$this->model->loadRDF( $termIRI );
			$rdf = $this->model->getRDF();
			require VIEWPATH . 'Ontology/rdf.php';
		}
	}
	
	public function view( $params ) {
		list( $ontAbbr, $termIRI ) = $this->parseOntologyParameter( $params );
		if ( !is_null( $termIRI ) ) {
			$GLOBALS['show_query'] = false;
			$xslt = true;
		}
		if ( !is_null( $ontAbbr ) ) {
			$this->loadModel( 'Ontology' );
			if ( is_null( $termIRI ) ) {
				$this->model->loadOntology( $ontAbbr, $termIRI );
				$title = "Ontobee: $ontAbbr";
				$ontology = $this->model->getOntology();
				if ( empty( $ontology ) ) {
					throw new Exception ( "Invalid ontology." );
				}
				$annotations = $ontology->annotation;
				$query = $this->model->getQueries();
				require VIEWPATH . 'Ontology/ontology.php';
			} else {
				$this->model->loadOntology( $ontAbbr, $termIRI, null, false );
				$title = "Ontobee: $ontAbbr";
				$ontology = $this->model->getOntology();
				if ( empty( $ontology ) ) {
					throw new Exception ( "Invalid ontology." );
				}
				$ontologyList = $this->model->getAllOntology();
				$type = $this->model->askTermType( $termIRI );
				if ( in_array( $type, array(
						'Class',
				) ) ) {
					$this->model->loadClass( $termIRI );
					$term = $this->model->getTerm();
					$annotations = $term->annotation;
					$query = $this->model->getQueries();
					require VIEWPATH . 'Ontology/class.php';
				} else if ( in_array( $type, array(
					'ObjectProperty',
					'DatatypeProperty',
					'AnnotationProperty',
				) ) ) {
					$this->model->loadProperty( $termIRI, $type );
					$term = $this->model->getTerm();
					$annotations = $term->annotation;
					$query = $this->model->getQueries();
					require VIEWPATH . 'Ontology/property.php';
				} else if ( in_array( $type, array(
					'Instance',
				) ) ) {
					$this->model->loadInstance( $termIRI );
					$term = $this->model->getTerm();
					$annotations = $term->annotation;
					$query = $this->model->getQueries();
					require VIEWPATH . 'Ontology/instance.php';
				} else {
					throw new Exception( "Incorrect ontology term type." );
				}
					 
			}
		} else {
			throw new Exception( "Ontology is not specified." );
		}
	}
	
	public function catalog( $params = array() ) {
		list( $ontAbbr, $termIRI ) = $this->parseOntologyParameter( $params );
		if ( !is_null( $ontAbbr ) ) {
			if ( array_key_exists( 'letter', $params ) ) {
				$letter = strtoupper( $params['letter'] );
			} else if ( array_key_exists( 'l', $params ) ) {
				$letter = strtoupper( $params['l'] );
			} else {
				$letter = '*';
			}
			
			if ( array_key_exists( 'page', $params ) ) {
				$page = $params['page'];
			} else if ( array_key_exists( 'p', $params ) ) {
				$page = $params['p'];
			} else {
				$page = 1;
			}
			
			if ( array_key_exists( 'max', $params ) ) {
				$listMaxTerms = $params['max'];
			} else if ( array_key_exists( 'm', $params ) ) {
				$listMaxTerms = $params['m'];
			} else {
				$listMaxTerms = $GLOBALS['ontology']['term_max_per_page'][0];
			}
			
			$this->loadModel( 'Ontology' );
			$this->model->loadOntology( $ontAbbr, $termIRI, null, false );
			$title = "Ontobee: $ontAbbr";
			$ontology = $this->model->getOntology();
			if ( !empty( $ontology ) ) {
				list( $terms, $letters, $page, $pageCount ) = $this->model->getTermList( $termIRI, null, $letter, $page, $listMaxTerms );
				require VIEWPATH . 'Ontology/catalog.php';
			} else {
				throw new Exception ( "Invalid ontology." );
			}
		} else {
			throw new Exception( "Invalid parameters." );
		}
	}
}



?>