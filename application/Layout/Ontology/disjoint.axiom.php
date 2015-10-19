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
 * @file disjoint.php
 * @author Edison Ong
 * @since Oct 17, 2015
 * @comment 
 */
 
use View\Helper;

if ( !$this ) {
	exit(header('HTTP/1.0 403 Forbidden'));
}

class DisjointAxiom {
	public static function show( $ontology, $term ) {
		$html = '';
		if ( !empty ( $term->axiom['disjoint'] ) ) {
			$rootURL = SITEURL . "ontology/$ontology->ontology_abbrv?iri=";

			$operations = $GLOBALS['ontology']['restriction']['operation'];
			$types = $GLOBALS['ontology']['restriction']['type'];

			$html =
<<<END
<div class="section-title">Disjoints</div>
<div class="section"><ul>
END;

			foreach ( $term->axiom['disjoint'] as $data ) {
				$axiom = Helper::writeRecursiveManchester( $rootURL, $data, $term->related );
				$html .=
<<<END
<li>{$GLOBALS['call_function']( Helper::trimBracket( $axiom ) )}</li>
END;
			}

			$html .=
<<<END
</ul></div>
END;
		}

		return $html;
	}
}

?>

<!-- InverseOf Axiom Display Start -->
<?php 
echo Helper::tidyHTML( DisjointAxiom::show( $ontology, $term ) );
?>
<!-- InverseOf Axiom Display End -->