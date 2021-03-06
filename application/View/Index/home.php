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
 * @file home.php
 * @author Yongqun Oliver He
 * @author Zuoshuang Allen Xiang
 * @author Edison Ong
 * @since Sep 4, 2015
 * @comment 
 */
 
if (!$this) {
	exit(header('HTTP/1.0 403 Forbidden'));
}

?>

<?php require TEMPLATE . 'header.default.dwt.php'; ?>

<h3 class="head3_darkred">Welcome to Ontobee!</h3>

<p><strong>Ontobee: </strong>A <a href="http://www.w3.org/DesignIssues/LinkedData.html">linked data</a> server designed for ontologies. Ontobee is aimed to  facilitate ontology data sharing, visualization, query, integration, and analysis. Ontobee dynamically <a href="http://www.w3.org/2001/tag/doc/httpRange-14/2007-05-31/HttpRange-14">dereferences</a> and presents individual ontology term URIs to (i) <em>HTML web pages</em> for user-friendly web browsing and navigation, and to  (ii) <em>RDF source code </em>for <a href="http://en.wikipedia.org/wiki/Semantic_Web">Semantic Web</a> applications. Ontobee is the default linked data server for most <a href="http://obofoundry.org/">OBO Foundry  library ontologies</a>. Ontobee has also been used for many non-OBO ontologies. </p>

<?php require TEMPLATE . 'search.keyword.php'; ?>

<form action="<?php echo SITEURL ?>search/redirect" method="get" id="home-redirect" style="margin-top:16px;">
  <div class="ui-widget">Jump to http://purl.obolibrary.org/obo/
    <input id="redirect-id" name="id" size="20" />
    <input type="submit" name="submit" value="Go" />
</div>
</form>

<p>Currently Ontobee has been applied for the following ontologies: </p>
<div id="ontologyTable">
<table id="ontologyList" class="tablesorter" border="0" cellpadding="2" style="">
<thead>
<tr>
<th width="5%"><strong>No.</strong></th>
<th width="20%"><strong>Ontology Prefix</strong></th>
<th width="45%"><strong>Ontology Full Name</strong></th>
<th width="12%"><strong>OBO <img id="obo-open" height="80%" src="<?php echo SITEURL; ?>public/images/question_frame.png"></strong></th>
<!--  temporary remove domain display <th width="15%"><string>Domain</th> -->
<th width="18%"><strong>List of Terms</strong></th>
</tr>
</thead>

<div id="obo-legend" title="OBO Legend" style="display:none">
  <p align="left">
  <strong>F</strong>: <u>F</u>oundry</br>
  <strong>L</strong>: <u>L</u>ibrary</br>
  <strong>N</strong>: <u>N</u>ot Specified/<u>N</u>o</p>
</div>

<tbody>
<?php
/* Removed default ontology table display order
 * Initially, foundry ontologies will display first
 * As Oliver requested on 9/10/2016, all ontologies will be displayed by alphabetical order
usort( $ontologies, function( $a, $b ) {
	if ( empty( $a->foundry ) ) {
		$num1 = INF;
	} else {
		$num1 = ord( strtolower( substr( $a->foundry, 0, 1 ) ) );
	}
	if ( empty( $b->foundry ) ) {
		$num2 = INF;
	} else {
		$num2 = ord( strtolower( substr( $b->foundry, 0, 1 ) ) );
	}
	if ( $num1 != $num2 ) {
		return strcmp( $num1, $num2 );
	} else {
		return strcmp( $a->ontology_abbrv, $b->ontology_abbrv );
	}
} );
*/
$index = 0;
foreach ( $ontologies as $key => $ontology ) {
	$index += 1;
	if ( $index % 2 == 0 ) {
		$bgcolor = 'even';
	} else {
		$bgcolor = 'odd';
	}
	$site = SITEURL;
	if ( isset( $ontology->foundry ) && !is_null( $ontology->foundry ) && !empty( $ontology->foundry ) ) {
		$foundry = $ontology->foundry[0];
	} else {
		$foundry = 'N';
	}
	/*
	 * Temporary remove domain display
	 * 
	if ( isset( $ontology->domain ) && !is_null( $ontology->domain ) && !empty( $ontology->domain ) ) {
		$domain = $ontology->domain;
	} else {
		$domain = '-';
	}
	*/
	echo
<<<END
<tr class="$bgcolor" align="center">
<td><strong>$index</strong></td>
<td><a href="{$site}ontology/$ontology->ontology_abbrv">$ontology->ontology_abbrv</a></td>
<td>$ontology->ontology_fullname
END;
	/*
	if ( isset( $ontology->license ) && !is_null( $ontology->license ) && !empty( $ontology->license ) ) {
		$license = preg_split( '/[|]/', $ontology->license );
		echo 
<<<END
<a href="$license[2]"><img height="15px" src="$license[1]" alt="$license[0]"></a>
END;
	}
	*/
	
	/*
	 * Temporary remove domain display
	 *
	echo
<<<END
</td>
<td>$foundry</td>
<td>$domain</td>
<td>
<a href="{$site}listTerms/$ontology->ontology_abbrv?format=xlsx" title="Excel XLSX File"><img src="{$site}public/images/Excel_xlsx_Logo.png" alt="Excel XLSX format" width="24" height="24" border="0"></a>
<a href="{$site}listTerms/$ontology->ontology_abbrv?format=tsv" title="Tab Separated Text File"><img src="{$site}public/images/Text_tsv_Logo.png" alt="Tab Separated format" width="24" height="24" border="0"></a>
</td>
</tr>
END;
	*/
	echo
<<<END
</td>
<td>$foundry</td>
<td>
<a href="{$site}listTerms/$ontology->ontology_abbrv?format=xlsx" title="Excel XLSX File"><img src="{$site}public/images/Excel_xlsx_Logo.png" alt="Excel XLSX format" width="24" height="24" border="0"></a>
<a href="{$site}listTerms/$ontology->ontology_abbrv?format=tsv" title="Tab Separated Text File"><img src="{$site}public/images/Text_tsv_Logo.png" alt="Tab Separated format" width="24" height="24" border="0"></a>
</td>
</tr>
END;
}
?>
</tbody>
</table>
</div>

<script type="text/javascript">
$(document).ready(function() 
	    { 
	        $("#ontologyList").tablesorter({
	            headers: {
	                0: {
	                    sorter: false
	                },
	                4: {
	                    sorter: false
	            	}
	            },
	            //sortList: [[1,0]],
	    	});
	    } 
	);
//Auto-reorder number
$("#ontologyList").bind("sortStart",function() {
	var clone = $("#ontologyList").clone(true);
	clone[0].setAttribute("id", "ontologyListOverlay");
	$("#ontologyList").hide();
	$("#ontologyTable").append(clone[0]);
}).bind("sortEnd",function() { 
    var i = 0;
    $("#ontologyList").find("tr:gt(0)").each(function(){
        i++;
        $(this).find("td:eq(0)").html("<strong>" + i + "<strong>");
        if ( i % 2 == 0 ) {
        	$(this).removeClass("odd even").addClass("even");
        } else {
        	$(this).removeClass("odd even").addClass("odd");
        }
    });
    $("#ontologyListOverlay").remove();
	$("#ontologyList").show();
});
//Pop-up OBO legend
$(function() {
    $( "#obo-legend" ).dialog({
    	modal: true,
    	autoOpen: false,
    	closeOnEscape: false,
    	open: function(event, ui) {
            $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
        }
    });

    $( "#obo-open" ).hoverIntent( function() {
        $( "#obo-legend" ).dialog( "open" );
    }, function() {
        $( "#obo-legend" ).dialog( "close" );
    });
});
</script>

<p><strong>Please cite the following reference for Ontobee: </strong></p>

<p>Ong E, Xiang Z, Zhao B, Liu Y, Lin Y, Zheng J, Mungall C, Courtot M, Ruttenberg A, He Y. <a href="http://nar.oxfordjournals.org/content/45/D1/D347">Ontobee: A linked ontology data server to support ontology term dereferencing, linkage, query, and integration</a>. <em>Nucleic Acid Research</em>. 2017 Jan 4;45(D1):D347-D352. PMID: <a href="https://www.ncbi.nlm.nih.gov/pubmed/27733503">27733503</a>.	PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5210626/">PMC5210626</a>.
<p><strong>Ontobee ICBO proceeding paper: </strong></p>

<p>Xiang Z, Mungall C, Ruttenberg A, He Y. <a href="doc/Ontobee_ICBO-2011_Proceeding.pdf">Ontobee: A Linked Data Server and Browser for Ontology Terms</a>. <em>Proceedings of the 2nd International Conference on Biomedical Ontologies (ICBO)</em>, July 28-30, 2011, Buffalo, NY, USA. Pages 279-281. URL: <a href="http://ceur-ws.org/Vol-833/paper48.pdf">http://ceur-ws.org/Vol-833/paper48.pdf</a>. </p>
<p>&nbsp;</p>

<?php require TEMPLATE . 'footer.default.dwt.php'; ?>
