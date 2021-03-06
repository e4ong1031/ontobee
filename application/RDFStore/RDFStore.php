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
 * @file RDFStore.php
 * @author Zuoshuang Allen Xiang
 * @author Edison Ong
 * @author Bin Zhao
 * @since Sep 4, 2015
 * @comment 
 */
namespace RDFStore;

use Exception;

use RDFStore\CurlRequest;
use RDFStore\SPARQLQuery;

class RDFStore {
	protected $endpoint;
	
	protected $prefixNS, $search;
	
	protected $sparql;
	
	public function __construct( $endpoint ) {
		$this->endpoint =  $endpoint;
		$this->prefixNS = $GLOBALS['ontology']['namespace'];
		$this->search = $GLOBALS['search'];
		$this->sparql = new SPARQLQuery( $endpoint );
	}
	
	public function ping() {
		$this->sparql->clear();
		$this->sparql->add( 'ask', 'ASK WHERE{ ?s ?p ?o .}' );
		$this->sparql->add( 'select', 'SELECT ?s WHERE{ ?s ?p ?o . } LIMIT 1' );
		$jsons = $this->sparql->execute();
		$ask = json_decode( $jsons['ask'], true );
		$select = RDFQueryHelper::parseSPARQLResult( $jsons['select'] );
		if ( $ask && !empty( $select ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	public function search( $graphs, $keywords, $limit, $exact = false ) {
		$propertiesQuery = '<' . join( '>,<', $this->search['property'] ) . '>';
		
		/* or exact one ontololgy */
		if ( sizeof( $graphs ) == 1 ) {
			$graph = current( $graphs );
			
			/* URL search */
			if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $keywords, $matches, PREG_SET_ORDER ) ) {
				if ( $matches[0][2] == '' ) {
					$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][1] . '_' . $matches[0][3];
				} else {
					$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][2] . '_' . $matches[0][3];
				}
				
				$query =
<<<END
SELECT * FROM <$graph> WHERE{
	?s ?p ?o .
	FILTER ( ?p in ( $propertiesQuery ) ) .
	FILTER ( ?s in ( <$searchTermURL> ) ) .
	OPTIONAL { ?s <http://www.w3.org/2002/07/owl#deprecated> ?d }
}
LIMIT $limit
END;
			}
			/* Keyword search */
			else {
				$keywords = preg_replace( "/([()])/", "[$1]", $keywords );
				if ( $exact ) {
					$keywords = "^$keywords\$";
				}
				$query =
<<<END
SELECT * FROM <$graph> WHERE{
	?s ?p ?o .
	FILTER ( ?p in ( $propertiesQuery ) ) .
	FILTER ( isIRI( ?s ) ) .
	FILTER ( REGEX( STR( ?o ), "$keywords", "i" ) ) .
	OPTIONAL { ?s <http://www.w3.org/2002/07/owl#deprecated> ?d }
}
LIMIT $limit
END;
			}
		} 
		/* For more than one ontololgy */
		else {
			/* URL search */
			if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $keywords, $matches, PREG_SET_ORDER ) ) {
				if ( $matches[0][2] == '' ) {
					$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][1] . '_' . $matches[0][3];
				} else {
					$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][2] . '_' . $matches[0][3];
				}
	
				$query =
<<<END
SELECT * WHERE {
	GRAPH ?g {
		?s ?p ?o .
		FILTER ( ?p in ( $propertiesQuery ) ) .
		FILTER ( ?s in ( <$searchTermURL> ) ) .
		OPTIONAL { ?s <http://www.w3.org/2002/07/owl#deprecated> ?d }
	} .
}
LIMIT $limit
END;
			}
			/* Keyword search */
			else {
				$keywords = preg_replace( "/([()])/", "[$1]", $keywords );
				$keypattern = preg_split( '/[,. ]/', $keywords );
				$keypattern = $keypattern[0];
				if ( $exact ) {
					$keywords = "^$keywords\$";
				}
				if ( strlen( $keypattern ) < 4 ) {
					$query=
<<<END
SELECT * WHERE {
	GRAPH ?g {
		?s ?p ?o .
		FILTER ( ?p in ( $propertiesQuery ) ) .
		FILTER ( isIRI( ?s ) ) .
		FILTER ( REGEX( STR( ?o ), "$keywords", "i" ) ) .
		OPTIONAL { ?s <http://www.w3.org/2002/07/owl#deprecated> ?d }
	}
}
LIMIT $limit
END;
				} else {
					$query=
<<<END
SELECT * WHERE {
	GRAPH ?g {
		?s ?p ?o .
		FILTER ( ?p in ( $propertiesQuery ) ) .
		FILTER ( isIRI( ?s ) ) .
		?o bif:contains "'$keypattern*'" .
		FILTER ( REGEX( STR( ?o ), "$keywords", "i" ) ) .
		OPTIONAL { ?s <http://www.w3.org/2002/07/owl#deprecated> ?d }
	}
}
LIMIT $limit
END;
				}
			}
		}
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$queries[] = $query;
		
		$results = RDFQueryHelper::parseSPARQLResult( $json );
		
		$match = RDFQueryHelper::parseSearchResult( $keywords, $results, $graphs );
		return array( $match, $query );
	}
	
	public function countType( $graph, $typeIRI ) {
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT COUNT( DISTINCT ?class ) as ?count FROM <$graph> WHERE {
	{
		?class a <$typeIRI> .
		FILTER ( isIRI( ?class ) ).
	}
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$count = RDFQueryHelper::parseCountResult( $json );
		return array( $count, $query );
	}
	
	public function selectOntology( $graph ) {
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT COUNT(*) FROM <$graph> WHERE {
	?s rdf:type owl:Class .
	?s rdfs:label ?l .
	FILTER ( isIRI( ?s ) ).
	OPTIONAL {
		?s <http://purl.obolibrary.org/obo/IAO_0000118> ?alt_names
	} .
	OPTIONAL {
		?s <http://purl.obolibrary.org/obo/IAO_0000115> ?definition
	} .
	OPTIONAL {
		?s rdfs:subClassOf ?pTerm .
		FILTER ( isIRI( ?pTerm ) ) .
		?pTerm rdfs:label ?pLabel
	} .
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$limit = RDFQueryHelper::parseCountResult( $json );
		
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT * FROM <$graph> WHERE {
	?s rdf:type owl:Class .
	?s rdfs:label ?l .
	FILTER ( isIRI( ?s ) ).
	OPTIONAL {
		?s <http://purl.obolibrary.org/obo/IAO_0000118> ?alt_names
	} .
	OPTIONAL {
		?s <http://purl.obolibrary.org/obo/IAO_0000115> ?definition
	} .
	OPTIONAL {
		?s rdfs:subClassOf ?pTerm .
		FILTER ( isIRI( ?pTerm ) ) .
		?pTerm rdfs:label ?pLabel
	} .
}
limit $limit
END;
		$this->sparql->add( 'class', $query, '', 'application/sparql-results+json');
		$queries[] = $query;
		
		$typeQuery = '<' . join('>, <', $GLOBALS['ontology']['signature_term_type']) . '>';
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT * FROM <$graph> WHERE {
	?s rdf:type ?o .
	?s rdfs:label ?l .
	FILTER ( ?o in( $typeQuery ) ).
	FILTER ( isIRI( ?s ) ).
	OPTIONAL {
		?s <http://purl.obolibrary.org/obo/IAO_0000118> ?alt_names
	} .
	OPTIONAL {
		?s <http://purl.obolibrary.org/obo/IAO_0000115> ?definition
	} .
	OPTIONAL {
		?s rdfs:subPropertyOf ?pTerm .
		FILTER ( isIRI( ?pTerm ) ) .
		?pTerm rdfs:label ?pLabel
	} .
}
limit 10000
END;
		$this->sparql->add( 'type', $query, '', 'application/sparql-results+json');
		$queries[] = $query;
		
		$results = $this->sparql->execute();
		
		$output = array();
		$output['class'] = RDFQueryHelper::parseSPARQLResult( $results['class'] );
		$output['type'] = RDFQueryHelper::parseSPARQLResult( $results['type'] );
		
		return array( $output, $queries );
	}
	
	public function selectOntologyAnnotation( $graph, $ontIRI ) {
		$query =
<<<END
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?p ?o FROM <$graph> WHERE {
	?s a owl:Ontology .
	?s ?p ?o
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$annotation = array();
		foreach ( $result as $entry ) {
			$annotation[$entry['p']][] = $entry['o'];
		}
		return array( $annotation, $query );
	}
	
	public function selectOntologyProperty( $graph, $typeIRI ) {
		$query =
<<<END
SELECT DISTINCT ?property FROM <$graph> WHERE {
	{
		?property a <$typeIRI> .
		FILTER ( isIRI( ?property ) )
END;
		foreach ( $GLOBALS['alias']['type'] as $alias => $iri ) {
			if ( $iri == $typeIRI ) {
				$query .=
<<<END

	} UNION {
		?property a <$alias> .
		FILTER ( isIRI( ?property ) )		
END;
			}
		}
		
		$query .=
<<<END

	}
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$property = RDFQueryHelper::parseEntity( $result, 'property' );
		return array( $property, $query );
	}
	
	public function selectTermType( $graph, $termIRI ) {	
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
SELECT * FROM <$graph> WHERE {
	?s rdf:type ?o .
	FILTER ( ?s = <$termIRI> )
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$type = RDFQueryHelper::parseEntity( $result, 's', 'o' );
		$type = $type[$termIRI];
		foreach ( $type as $index => $typeIRI ) {
			if ( array_key_exists( $typeIRI, $GLOBALS['alias']['type'] ) ) {
				$type[$index] = $GLOBALS['alias']['type'][$typeIRI];
			}
		}
		$type = array_unique( $type );
		
		foreach ( $GLOBALS['ontology']['type'] as $majorType ) {
			if ( in_array( $majorType, $type ) ) {
				return array( $type[array_search($majorType, $type)], $query );
			}
		}
		
		return null;
	}
	
	public function selectAllTermType( $graph, $termIRIs ) {
		$termsQuery = '<' . join( '>, <' , $termIRIs ) . '>';
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
SELECT * FROM <$graph> WHERE {
	?s rdf:type ?o .
	FILTER (?s in ( $termsQuery ) )
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$types = RDFQueryHelper::parseEntity( $result, 's', 'o' );
		$output = array();
		foreach ( $types as $iri => $type ) {
			if ( in_array( $GLOBALS['ontology']['type']['Instance'], $type ) ) {
				$output[$iri] = $GLOBALS['ontology']['type']['Instance'];
			} else {
				foreach ( $type as $index => $typeIRI ) {
					if ( array_key_exists( $typeIRI, $GLOBALS['alias']['type'] ) ) {
						$type[$index] = $GLOBALS['alias']['type'][$typeIRI];
					}
				}
				$type = array_unique( $type );
				while ( !empty( $type ) ) {
					if ( in_array( $type[0], $GLOBALS['ontology']['type'] ) ) {
						$output[$iri] = array_shift( $type );
					} else {
						array_splice( $type, 0, 1 );
					}
				}
			}
		}
		return array( $output, $query );
	}
	
	public function selectTermLabel( $graph, $termIRI ) {
		$query =
<<<END
SELECT * FROM <$graph> WHERE {
	?s <http://www.w3.org/2000/01/rdf-schema#label> ?o .
	FILTER ( ?s = <$termIRI> )
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$label = RDFQueryHelper::parseEntity( $result, 's', 'o' );
		return array( $label[$termIRI], $query );
	}
	
	public function selectAllTermLabel( $graph, $termIRIs ) {
		$termsQuery = '<' . join( '>, <' , $termIRIs ) . '>';
		$query =
<<<END
SELECT * FROM <$graph> WHERE {
	?s <http://www.w3.org/2000/01/rdf-schema#label> ?o .
	FILTER ( ?s in ( $termsQuery ) )
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$labels = RDFQueryHelper::parseEntity( $result, 's', 'o' );
		return array( $labels, $query );
	}
	
	public function describe( $graph, $termIRI ) {
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE <$termIRI>
FROM <$graph>
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query, '', 'application/rdf+json' );
		# Potential virtuoso problem reported in ontobee
		#$json = preg_replace( '/\'\);\ndocument.writeln\(\'/', '', $json );
		$describe = RDFQueryHelper::parseRDF( $json, $termIRI );
		return array( $describe, $query );
	}
	
	public function describeAll( $graph, $termIRIs ) {
		$termsQuery = '<' . join( '> <' , $termIRIs ) . '>';
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE $termsQuery
FROM <$graph>
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query, '', 'application/rdf+json' );
		$json = json_decode( $json, true );
		$describes = array();
		foreach ( $termIRIs as $index => $termIRI ) {
			$describe = RDFQueryHelper::parseRDF( $json, $termIRI, false );
			$describes[$termIRI] = $describe;
		}
		return array( $describes, $query );
	}
	
	public function describeClass( $graph, $classIRI ) {
		$this->sparql->clear();
		
		# Describe Term
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE <$classIRI>
FROM <$graph>
END;
		$this->sparql->add( 'describe', $query, '', 'application/rdf+json');
		$queries[] = $query;
		
		# Transitive Super Classes
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT ?path ?link ?label FROM <{$graph}> WHERE {
	{
		SELECT ?s ?o ?label WHERE {
			{
				?s rdfs:subClassOf ?o .
				FILTER (isIRI(?o)).
				OPTIONAL {
					?o rdfs:label ?label .
					FILTER(LANG(?label) = "" || LANG(?label) = 'en')
				}
			} UNION {
				?s owl:equivalentClass ?s1 .
				?s1 owl:intersectionOf ?s2 .
				?s2 rdf:first ?o  .
				FILTER (isIRI(?o))
				OPTIONAL {
					?o rdfs:label ?label .
					FILTER(LANG(?label) = "" || LANG(?label) = 'en')
				}
			}
			FILTER ( ?s != ?o )
		}
	}
	OPTION (TRANSITIVE, t_in(?s), t_out(?o), t_step (?s) as ?link, t_step ('path_id') as ?path).
	FILTER (isIRI(?o)).
	FILTER (?s= <$classIRI>)
}
END;
		$this->sparql->add( 'transitiveSupClass', $query );
		$queries[] = $query;
		
		# Annotation's other information
		$query =
<<<END
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT * FROM <$graph> WHERE {
	?nodeID owl:annotatedSource <$classIRI> .
	#?nodeID rdf:type owl:Annotation .
	?nodeID owl:annotatedProperty ?annotatedProperty .
	?nodeID owl:annotatedTarget ?annotatedTarget .
	?nodeID ?aaProperty ?aaPropertyTarget .
	OPTIONAL {?annotatedProperty rdfs:label ?annotatedPropertyLabel} .
	OPTIONAL {?aaProperty rdfs:label ?aaPropertyLabel} .
	FILTER ( isLiteral( ?annotatedTarget ) ) .
	FILTER ( ?aaProperty NOT IN ( owl:annotatedSource, rdf:type, owl:annotatedProperty, owl:annotatedTarget ) )
}
END;
		$this->sparql->add( 'annotation_annotation', $query );
		$queries[] = $query;
		
		# Use by other terms in current Ontology
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?ref ?refp ?label ?o FROM <$graph> WHERE {
	?ref ?refp ?o .
	FILTER ( ?refp IN ( owl:equivalentClass, rdfs:subClassOf ) ) .
	OPTIONAL {
		?ref rdfs:label ?label
		FILTER(LANG(?label) = "" || LANG(?label) = 'en')
	} .
	{
		{
			SELECT ?s ?o FROM <$graph> WHERE {
				?o ?p ?s .
				FILTER ( ?p IN ( rdf:first, rdf:rest, owl:intersectionOf, owl:unionOf, owl:someValuesFrom, owl:hasValue, owl:allValuesFrom, owl:complementOf, owl:inverseOf, owl:onClass, owl:onProperty ) )
			}
		}
		OPTION ( TRANSITIVE, t_in( ?s ), t_out( ?o ), t_step( ?s ) as ?link ).
		FILTER ( ?s= <$classIRI> )
	}
}
ORDER BY ?label
END;
		$this->sparql->add( 'term', $query );
		$queries[] = $query;
		
		# Exist in other Ontology
		$query =
<<<END
SELECT DISTINCT ?g WHERE {
	GRAPH ?g {
		<$classIRI> ?p ?o
	}
}
END;
		$this->sparql->add( 'ontology', $query );
		$queries[] = $query;
		
		$results = $this->sparql->execute();
		
		$describeArray = json_decode( $results['describe'], true );
		$class['describe'] = RDFQueryHelper::parseRDF( $results['describe'], $classIRI );
		
		$class['annotation_annotation'] = RDFQueryHelper::parseSPARQLResult( $results['annotation_annotation'] );
		
		$class['transitiveSupClass'] = RDFQueryHelper::parseTransitivePath( 
			RDFQueryHelper::parseSPARQLResult( $results['transitiveSupClass'] ) 
		);
		
		$usage = array();
		$usage['term'] = RDFQueryHelper::parseSPARQLResult( $results['term'] );
		$usage['ontology'] = RDFQueryHelper::parseSPARQLResult( $results['ontology'] );
		$class['usage'] = $usage;
		
		$axiom = array( 'super' => array(), 'equivalent' => array(), 'disjoint' => array() );
		if ( array_key_exists( $this->prefixNS['rdfs'] . 'subClassOf', $describeArray[$classIRI] ) ) {
			$subclassof = $describeArray[$classIRI][$this->prefixNS['rdfs'] . 'subClassOf'];
			foreach ( $subclassof as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['super'][] = $node['value'];
				} else {
					$axiom['super'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		if ( array_key_exists( $this->prefixNS['owl'] . 'equivalentClass', $describeArray[$classIRI] ) ) {
			$equivalent = $describeArray[$classIRI][$this->prefixNS['owl'] . 'equivalentClass'];
			foreach ( $equivalent as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['equivalent'][] = $node['value'];
				} else {
					$axiom['equivalent'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		if ( array_key_exists( $this->prefixNS['owl'] . 'disjointWith', $describeArray[$classIRI] ) ) {
			$disjoint = $describeArray[$classIRI][$this->prefixNS['owl'] . 'disjointWith'];
			foreach ( $disjoint as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['disjoint'][] = $node['value'];
				} else {
					$axiom['disjoint'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		$class['axiom'] = $axiom;
		
		return array( $class, $queries );
	}
	
	public function selectSubClass( $graph, $termIRI ) {
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?term ?label ?subTerm FROM <$graph> WHERE {
	{
		?term rdfs:subClassOf <$termIRI> .
		FILTER (isIRI(?term)).
		OPTIONAL {
			?term rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		} .
		OPTIONAL {?subTerm rdfs:subClassOf ?term}
	} UNION {
		?term owl:equivalentClass ?s1 .
		FILTER (isIRI(?term)).
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first <$termIRI> .
		OPTIONAL {
			?term rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		} .
		OPTIONAL {?subTerm rdfs:subClassOf ?term}
	} UNION {
		?term rdfs:subClassOf <$termIRI> .
		FILTER (isIRI(?term)).
		OPTIONAL {
			?term rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		} .
		OPTIONAL {
			?subTerm owl:equivalentClass ?s1 .
			?s1 owl:intersectionOf ?s2 .
			?s2 rdf:first ?term
		}
	} UNION {
		?term owl:equivalentClass ?s1 .
		FILTER (isIRI(?term)).
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first <$termIRI> .
		OPTIONAL {
			?term rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		} .
		OPTIONAL {?subTerm owl:equivalentClass ?s3 .
		?s3 owl:intersectionOf ?s4 .
		?s4 rdf:first ?term}
	}
}
END;
		
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$subClasses = RDFQueryHelper::parseSPARQLResult( $json );
		return array( $subClasses, $query );
	}
	
	public function selectDirectSubClass( $graph, $termIRI ) {
		$query =
		<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?term ?label ?subTerm FROM <$graph> WHERE {
	{
		?term rdfs:subClassOf <$termIRI> .
		FILTER (isIRI(?term)).
		OPTIONAL {
			?term rdfs:label ?label .
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		}
		OPTIONAL {?subTerm rdfs:subClassOf ?term}
	}
}
END;
	
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$subClasses = RDFQueryHelper::parseSPARQLResult( $json );
		return array( $subClasses, $query );
	}
	
	public function selectSupClass( $graph, $termIRI ) {
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?class ?label FROM <$graph> WHERE {
	{
		<$termIRI> rdfs:subClassOf ?class .
		FILTER (isIRI(?class)).
		OPTIONAL {
			?class rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		}
	} UNION {
		<$termIRI> owl:equivalentClass ?s1 .
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first ?class  .
		FILTER (isIRI(?class))
		OPTIONAL {
			?class rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		}
	}
}
END;
		
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$supClasses = RDFQueryHelper::parseSPARQLResult( $json );
		return array( $supClasses, $query );
	}
	
	public function describeProperty( $graph, $propertyIRI ) {
		$this->sparql->clear();
	
		# Describe Term
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE <$propertyIRI>
FROM <$graph>
END;
		$this->sparql->add( 'describe', $query, '', 'application/rdf+json');
		$queries[] = $query;
	
		# Transitive Super Properties
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT ?path ?link ?label FROM <{$graph}> WHERE {
	{
		SELECT ?s ?o ?label WHERE {
			{
				?s rdfs:subPropertyOf ?o .
				FILTER (isIRI(?o)).
				OPTIONAL {
					?o rdfs:label ?label .
					FILTER(LANG(?label) = "" || LANG(?label) = 'en')
				}
			} UNION {
				?s owl:equivalentProperty ?s1 .
				?s1 owl:intersectionOf ?s2 .
				?s2 rdf:first ?o  .
				FILTER (isIRI(?o))
				OPTIONAL {
					?o rdfs:label ?label .
					FILTER(LANG(?label) = "" || LANG(?label) = 'en')
				}
			}
			FILTER ( ?s != ?o )
		}
	}
	OPTION (TRANSITIVE, t_in(?s), t_out(?o), t_step (?s) as ?link, t_step ('path_id') as ?path).
	FILTER (isIRI(?o)).
	FILTER (?s= <$propertyIRI>)
}
END;
		$this->sparql->add( 'transitiveSupProperty', $query );
		$queries[] = $query;
	
		# Annotation's other information
		$query =
<<<END
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT * FROM <$graph> WHERE {
	?nodeID owl:annotatedSource <$propertyIRI> .
	#?nodeID rdf:type owl:Annotation .
	?nodeID owl:annotatedProperty ?annotatedProperty .
	?nodeID owl:annotatedTarget ?annotatedTarget .
	?nodeID ?aaProperty ?aaPropertyTarget .
	OPTIONAL {?annotatedProperty rdfs:label ?annotatedPropertyLabel} .
	OPTIONAL {?aaProperty rdfs:label ?aaPropertyLabel} .
	FILTER ( isLiteral( ?annotatedTarget ) ) .
	FILTER ( ?aaProperty NOT IN ( owl:annotatedSource, rdf:type, owl:annotatedProperty, owl:annotatedTarget ) )
}
END;
		$this->sparql->add( 'annotation_annotation', $query );
		$queries[] = $query;
	
		# Use by other terms in current Ontology
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?ref ?refp ?label ?o FROM <$graph> WHERE {
	?ref ?refp ?o .
	FILTER ( ?refp IN ( owl:equivalentClass, rdfs:subClassOf ) ) .
	OPTIONAL {
		?ref rdfs:label ?label
		FILTER(LANG(?label) = "" || LANG(?label) = 'en')
	} .
	{
		{
			SELECT ?s ?o FROM <$graph> WHERE {
				?o ?p ?s .
				FILTER ( ?p IN ( rdf:first, rdf:rest, owl:intersectionOf, owl:unionOf, owl:someValuesFrom, owl:hasValue, owl:allValuesFrom, owl:complementOf, owl:inverseOf, owl:onClass, owl:onProperty ) )
			}
		}
		OPTION ( TRANSITIVE, t_in( ?s ), t_out( ?o ), t_step( ?s ) as ?link ).
		FILTER ( ?s= <$propertyIRI> )
	}
}
ORDER BY ?label
END;
		$this->sparql->add( 'term', $query );
		$queries[] = $query;
	
		# Exist in other Ontology
		$query =
<<<END
SELECT DISTINCT ?g WHERE {
	GRAPH ?g {
		<$propertyIRI> ?p ?o
	}
}
END;
		$this->sparql->add( 'ontology', $query );
		$queries[] = $query;
		
		# Check if there exists a property which is inverseOf query property
		$query = 
<<<END
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT ?s FROM <$graph> WHERE {
?s owl:inverseOf ?o .
FILTER ( ?o = <$propertyIRI> ) .
FILTER ( isIRI( ?s ) ) .
}
END;
		$this->sparql->add( 'inverse', $query );
		$queries[] = $query;
		
		$results = $this->sparql->execute();
		
		$describeArray = json_decode( $results['describe'], true );
		$property['describe'] = RDFQueryHelper::parseRDF( $results['describe'], $propertyIRI );
		
		$property['annotation_annotation'] = RDFQueryHelper::parseSPARQLResult( $results['annotation_annotation'] );
	
		$property['transitiveSupProperty'] = RDFQueryHelper::parseTransitivePath(
				RDFQueryHelper::parseSPARQLResult( $results['transitiveSupProperty'] )
		);
		
		$usage = array();
		$usage['term'] = RDFQueryHelper::parseSPARQLResult( $results['term'] );
		$usage['ontology'] = RDFQueryHelper::parseSPARQLResult( $results['ontology'] );
		$property['usage'] = $usage;
		
		$axiom = array( 'super' => array(), 'equivalent' => array(), 'chain' => array(), 'disjoint' => array(), 'inverse' => array() );
		if ( array_key_exists( $this->prefixNS['rdfs'] . 'subPropertyOf', $describeArray[$propertyIRI] ) ) {
			$subclassof = $describeArray[$propertyIRI][$this->prefixNS['rdfs'] . 'subPropertyOf'];
			foreach ( $subclassof as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['super'][] = $node['value'];
				} else {
					$axiom['super'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		if ( array_key_exists( $this->prefixNS['owl'] . 'equivalentProperty', $describeArray[$propertyIRI] ) ) {
			$equivalent = $describeArray[$propertyIRI][$this->prefixNS['owl'] . 'equivalentProperty'];
			foreach ( $equivalent as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['equivalent'][] = $node['value'];
				} else {
					$axiom['equivalent'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		if ( array_key_exists( $this->prefixNS['owl'] . 'propertyChainAxiom', $describeArray[$propertyIRI] ) ) {
			$chain = $describeArray[$propertyIRI][$this->prefixNS['owl'] . 'propertyChainAxiom'];
			foreach ( $chain as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['chain'][] = $node['value'];
				} else {
					$axiom['chain'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		if ( array_key_exists( $this->prefixNS['owl'] . 'disjointWith', $describeArray[$propertyIRI] ) ) {
			$disjoint = $describeArray[$propertyIRI][$this->prefixNS['owl'] . 'disjointWith'];
			foreach ( $disjoint as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['disjoint'][] = $node['value'];
				} else {
					$axiom['disjoint'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		if ( array_key_exists( $this->prefixNS['owl'] . 'inverseOf', $describeArray[$propertyIRI] ) ) {
			$inverse = $describeArray[$propertyIRI][$this->prefixNS['owl'] . 'inverseOf'];
			foreach ( $inverse as $node ) {
				if ( $node['type'] == 'uri' ) {
					$axiom['inverse'][] = $node['value'];
				} else {
					$axiom['inverse'][] = RDFQueryHelper::parseRecursiveRDFNode( $describeArray, $node['value'] );
				}
			}
		}
		if ( sizeof( RDFQueryHelper::parseSPARQLResult( $results['inverse'] ) ) > 0 ) {
			foreach( RDFQueryHelper::parseSPARQLResult( $results['inverse'] ) as $inverse ) {
				$axiom['inverse'][] = $inverse['s'];
				$property['describe'][$this->prefixNS['owl'] . 'inverseOf'][] = array(
					"type" => 'uri',
					"value" => $inverse['s']
				);
			}
		}
		
		$property['axiom'] = $axiom;
	
		return array( $property, $queries );
	}
	
	public function selectSubProperty( $graph, $termIRI ) {
		$query =
		<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?term ?label ?subTerm FROM <$graph> WHERE {
	{
		?term rdfs:subPropertyOf <$termIRI> .
		FILTER (isIRI(?term)).
		OPTIONAL {
			?term rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		} .
		OPTIONAL {?subTerm rdfs:subPropertyOf ?term}
	} UNION {
		?term owl:equivalentProperty ?s1 .
		FILTER (isIRI(?term)).
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first <$termIRI> .
		OPTIONAL {
			?term rdfs:label ?label
			FILTER(LANG(?label) = "" || LANG(?label) = 'en')
		} .
		OPTIONAL {?subTerm rdfs:subPropertyOf ?term}
	}
}
END;
	
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$subProperties = RDFQueryHelper::parseSPARQLResult( $json );
		return array( $subProperties, $query );
	}
	
	public function selectInstance( $graph, $typeIRI ) {
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT ?instance ?label FROM <$graph> WHERE {
	?instance rdf:type <$typeIRI> .
	?instance rdfs:label ?label
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$instance = RDFQueryHelper::parseEntity( $result, 'instance', 'label' );
		return array( $instance, $query );
	}
	
	public function selectTermFromType( $graph, $typeIRI ) {
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
SELECT DISTINCT ?term ?label FROM <$graph> WHERE {
	{
		?term rdf:type <$typeIRI> .
		FILTER ( isIRI( ?term ) ) .
		OPTIONAL { ?term rdfs:label ?label }
END;
		foreach ( $GLOBALS['alias']['type'] as $alias => $iri ) {
			if ( $iri == $typeIRI ) {
				$query .=
<<<END

	} UNION {
		?term rdf:type <$alias> .
		FILTER ( isIRI( ?term ) ) .
		OPTIONAL { ?term rdfs:label ?label }			
END;
			}
		}
		
		$query .=
<<<END

	}
}
END;
		$json = SPARQLQuery::queue( $this->endpoint, $query );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$terms = RDFQueryHelper::parseEntity( $result, 'term', 'label' );
		return array( $terms, $query );
	}
	
	public function describeInstance( $graph, $instanceIRI ) {
		$this->sparql->clear();
	
		# Describe Term
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE <$instanceIRI>
FROM <$graph>
END;
		$this->sparql->add( 'describe', $query, '', 'application/rdf+json');
		$queries[] = $query;
	
		# Annotation's other information
		$query =
<<<END
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT * FROM <$graph> WHERE {
	?nodeID owl:annotatedSource <$instanceIRI> .
	#?nodeID rdf:type owl:Annotation .
	?nodeID owl:annotatedProperty ?annotatedProperty .
	?nodeID owl:annotatedTarget ?annotatedTarget .
	?nodeID ?aaProperty ?aaPropertyTarget .
	OPTIONAL {?annotatedProperty rdfs:label ?annotatedPropertyLabel} .
	OPTIONAL {?aaProperty rdfs:label ?aaPropertyLabel} .
	FILTER ( isLiteral( ?annotatedTarget ) ) .
	FILTER ( ?aaProperty NOT IN ( owl:annotatedSource, rdf:type, owl:annotatedProperty, owl:annotatedTarget ) )
}
END;
		$this->sparql->add( 'annotation_annotation', $query );
		$queries[] = $query;
	
		# Use by other terms in current Ontology
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT DISTINCT ?ref ?refp ?label ?o FROM <$graph> WHERE {
	?ref ?refp ?o .
	FILTER ( ?refp IN ( owl:equivalentClass, rdfs:subClassOf ) ) .
	OPTIONAL {
		?ref rdfs:label ?label
		FILTER(LANG(?label) = "" || LANG(?label) = 'en')
	} .
	{
		{
			SELECT ?s ?o FROM <$graph> WHERE {
				?o ?p ?s .
				FILTER ( ?p IN ( rdf:first, rdf:rest, owl:intersectionOf, owl:unionOf, owl:someValuesFrom, owl:hasValue, owl:allValuesFrom, owl:complementOf, owl:inverseOf, owl:onClass, owl:onProperty ) )
			}
		}
		OPTION ( TRANSITIVE, t_in( ?s ), t_out( ?o ), t_step( ?s ) as ?link ).
		FILTER ( ?s= <$instanceIRI> )
	}
}
ORDER BY ?label
END;
		$this->sparql->add( 'term', $query );
		$queries[] = $query;
	
		# Exist in other Ontology
		$query =
<<<END
SELECT DISTINCT ?g WHERE {
	GRAPH ?g {
		<$instanceIRI> ?p ?o
	}
}
END;
		$this->sparql->add( 'ontology', $query );
		$queries[] = $query;
	
		$results = $this->sparql->execute();
	
		$describeArray = json_decode( $results['describe'], true );
		$instance['describe'] = RDFQueryHelper::parseRDF( $results['describe'], $instanceIRI );
	
		$instance['annotation_annotation'] = RDFQueryHelper::parseSPARQLResult( $results['annotation_annotation'] );
	
		$usage = array();
		$usage['term'] = RDFQueryHelper::parseSPARQLResult( $results['term'] );
		$usage['ontology'] = RDFQueryHelper::parseSPARQLResult( $results['ontology'] );
		$instance['usage'] = $usage;
	
		return array( $instance, $queries );
	}
	
	public function exportTermRDF( $graph, $termIRI, $ontIRI) {
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE <$termIRI>
FROM <$graph>
END;
		$describe = SPARQLQuery::queue( $this->endpoint, $query, '', 'application/rdf+xml' );
		$queries[] = $query;
		
		preg_match_all( '/xmlns:([^=]*)="([^"]*)"/', $describe, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$describeNS[$match[1]] = $match[2];
		}
		
		$buffer = array();
		
		if ( preg_match_all( '/(<rdf:Description[\s\S]*?(?=(rdf:Description>)))/', $describe, $matches, PREG_PATTERN_ORDER ) ) {
			foreach ( $matches[0] as $match ) {
				$lines = preg_split( '/\n/', $match );
				array_shift( $lines );
				array_pop( $lines );
				foreach ( $lines as $line ) {
					preg_match_all( '/resource="([^"]+)"/', $line, $resources, PREG_PATTERN_ORDER );
					foreach ( $resources[1] as $resource ) {
						$related[$resource] = null;
					}
					# For backward-compatibility with Virtuoso version 6.2.1
					preg_match_all( '/<n0pred:(\S+) xmlns:n0pred="(\S+)"/', $line, $resources, PREG_SET_ORDER );
					foreach ( $resources as $resource ) {
						$related[$resource[2].$resource[1]] = null;
					}
				}
			}
			for ( $index = 0; $index < sizeof( $matches[0] ); $index++ ) {
				$buffer[] = $matches[1][$index] . $matches[2][$index];
			}
		}
		
		if ( !empty( $related ) ) {
			$this->sparql->clear();
			$relatedQuery = "<" . join( '>, <', array_keys( $related ) ) . ">";
			$query = 
<<<END
CONSTRUCT {
	?s <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?o
} FROM <$graph> WHERE { 
	?s <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?o .
	FILTER ( ?s in ( $relatedQuery ) )
}
END;
			$this->sparql->add( 'relatedType', $query, '', 'application/rdf+xml');
			$queries[] = $query;


			$relatedQuery = "<" . join( '>, <', array_keys( $related ) ) . ">";
			$query =
<<<END
CONSTRUCT {
	?s <http://www.w3.org/2000/01/rdf-schema#label> ?o
} FROM <$graph> WHERE {
	?s <http://www.w3.org/2000/01/rdf-schema#label> ?o .
	FILTER ( ?s in ( $relatedQuery ) )
}
END;
			$this->sparql->add( 'relatedLabel', $query, '', 'application/rdf+xml');
			$queries[] = $query;
			
			$results = $this->sparql->execute();
			
			if ( preg_match_all(
					'/(<rdf:Description[\s\S]*?(?=(rdf:Description>)))/',
					$results['relatedType'] . $results['relatedLabel'],
					$matches,
					PREG_PATTERN_ORDER
			) ) {
				for ( $index = 0; $index < sizeof( $matches[0] ); $index++ ) {
					$buffer[] = $matches[1][$index] . $matches[2][$index];
				}
			}
		}
		
		$rdf = join( PHP_EOL, $buffer );
		
		$rdf = preg_replace( '/\<\?xml[\s]?version[\s]?=[\s]?"[\d]+.[\d]"[^?]*\?>/', '', $rdf );
		
		foreach ( $this->prefixNS as $prefix => $namespace ) {
			$rdf = str_replace( "xmlns:$prefix=\"$namespace\"", '', $rdf );
			$rdf = str_replace( "rdf:resource=\"$namespace", "rdf:resource=\"&$prefix;", $rdf );
			$rdf = str_replace( "rdf:about=\"$namespace", "rdf:about=\"&$prefix;", $rdf );
			$rdf = str_replace( "rdf:datatype=\"$namespace", "rdf:datatype=\"&$prefix;", $rdf );
		}
		
		$header = '';
		foreach ( array_merge( $this->prefixNS, $describeNS ) as $prefix => $namespace ) {
			$header .= " xmlns:$prefix=\"$namespace\" ";
		}
		
		$rdf =
<<<END
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE rdf:RDF [
<!ENTITY obo 'http://purl.obolibrary.org/obo/'>
<!ENTITY owl 'http://www.w3.org/2002/07/owl#'>
<!ENTITY rdfs 'http://www.w3.org/2000/01/rdf-schema#'>
<!ENTITY rdf 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'>
<!ENTITY oboInOwl 'http://www.geneontology.org/formats/oboInOwl#'>
<!ENTITY foaf 'http://xmlns.com/foaf/0.1/'>
]>

<rdf:RDF$header>
$rdf
</rdf:RDF>
END;
		
		return array( $rdf, $queries );
	}
	
}

?>
