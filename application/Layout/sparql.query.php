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
 * @file sparql.query.php
 * @author Yongqun Oliver He
 * @author Zuoshuang Allen Xiang
 * @author Edison Ong
 * @since Sep 6, 2015
 * @comment 
 */

if ( !$this ) {
	exit( header( 'HTTP/1.0 403 Forbidden' ) );
}

?>

<script src="<?php echo SITEURL; ?>public/js/ontobee.sparql.js"></script>

<div><a href="javascript:switch_sparql();" id="href_switch_sparql">Show SPARQL queries used in this page</a></div>
<div id="div_sparql" style="display:none">
<pre style="white-space:pre-wrap;white-space:-moz-pre-wrap;white-space:-pre-wrap;white-space:-o-pre-wrap;word-wrap:break-word;">
<?php 
echo htmlspecialchars( join( '
======================================================================

' , $query ) ); 
?>
</pre>
</div>