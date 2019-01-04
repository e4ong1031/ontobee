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
 * @file batchsearch.php
 * @author Edison Ong
 * @since Jan 3, 2019
 * @comment 
 */
 

if ( !$this ) {
    exit( header( 'HTTP/1.0 403 Forbidden' ) );
}

$site = SITEURL;

?>

<?php require TEMPLATE . 'header.default.dwt.php'; ?>

<link href="<?php echo SITEURL; ?>public/css/ui.fancytree.css" rel="stylesheet" type="text/css" />
<script src="<?php echo SITEURL; ?>public/js/jquery/fancytree-all.js"></script>

<script>

$( function() {
    $( "#tree" ).fancytree( {
        checkbox: false,
        icons: false,
        selectMode: 1,
        minExpandLevel: 2,
        postinit: function( isReloading, isError ) {
            this.reactivate();
        },
        focus: function( event, data ) {
            data.node.scheduleAction( "activate", 2000 );
            $( ".common_adverse" ).click( function() {
                var radio = $( 'input[name=common_adverse]:checked' );
                $( "#adverse-label" ).val( radio.parent().text() );
                $( "#adverse-iri" ).val( radio.val() );
                $( "#adverse-label-display" ).text( 'Selected: ' + radio.parent().text() );
                $( "#adverse-iri-display" ).text( radio.val() );
                $( "#adverse-iri-display" ).attr( 'href', radio.val() );
            } );
        },
        activate: function( event, data ) {
            var node = data.node;
            if( node.data.href ){
                window.open( node.data.href, node.data.target );
            }
        }
    }).show();
} );

$( function () {
    function split( val ) {
        return val.split( /,\s*/ );
    }
    
    function extractLast( term ) {
        return split( term ).pop();
    }
    
    $( "#keywords" ).autocomplete( {
        source: function ( request, response ) {
            $.ajax({
                url: "http://sparql.hegroup.org/sparql/?default-graph-uri=&query=PREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0APREFIX+rdfs%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F2000%2F01%2Frdf-schema%23%3E%0D%0APREFIX+owl%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F2002%2F07%2Fowl%23%3E%0D%0ASELECT+%3Fo+%3Fl+WHERE+%7B%0D%0A%09%7B%0D%0A%09%09SELECT+DISTINCT+%3Fo+FROM+%3Chttp%3A%2F%2Fpurl.obolibrary.org%2Fobo%2Fmerged%2FOAE%3E+WHERE+%7B%0D%0A%09%09%09%7B%0D%0A%09%09%09%09%7B%0D%0A%09%09%09%09%09SELECT+%3Fs+%3Fo+WHERE+%7B%0D%0A%09%09%09%09%09%09%7B%0D%0A%09%09%09%09%09%09%09%3Fo+rdfs%3AsubClassOf+%3Fs+.%0D%0A%09%09%09%09%09%09%09FILTER+%28+isIRI%28+%3Fo+%29+%29+.%0D%0A%09%09%09%09%09%09%7D%0D%0A%09%09%09%09%09%7D%0D%0A%09%09%09%09%7D%0D%0A%09%09%09%09OPTION+%28TRANSITIVE%2C+t_in%28%3Fs%29%2C+t_out%28%3Fo%29%2C+t_step+%28%3Fs%29+as+%3Flink%2C+t_step+%28%27path_id%27%29+as+%3Fpath%29.%0D%0A%09%09%09%09FILTER+%28+isIRI%28+%3Fo+%29+%29.%0D%0A%09%09%09%09FILTER+%28+%3Fs+%3D+%3Chttp%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOAE_0000001%3E+%29%0D%0A%09%09%09%7D%0D%0A%09%09%7D%0D%0A%09%7D+.%0D%0A%09%3Fo+rdfs%3Alabel+%3Fl+.%0D%0A%09FILTER+%28+REGEX%28+STR%28+%3Fl+%29%2C+%22" + encodeURI( request.term ) + "%22%2C+%22i%22+%29+%29+.%0D%0A%7D&format=application%2Fsparql-results%2Bjson&debug=on&timeout=",
                type: "GET",
                data: request,
                dataType: 'jsonp',
                success: function ( data ) {
                    response( $.map( data.results.bindings, function ( entry ) {
                        return {
                            label: entry.l.value,
                            value: entry.o.value,
                        };
                    }));
                }
            });
        },
        select: function (event, ui) {
            $( "#adverse-label" ).val( ui.item.label );
            $( "#adverse-iri" ).val( ui.item.value );
            $( "#adverse-label-display" ).text( 'Selected: ' + ui.item.label );
            $( "#adverse-iri-display" ).text( ui.item.value );
            $( "#adverse-iri-display" ).attr( 'href', ui.item.value );
        }
    });

    $( ".common_adverse" ).click( function() {
        var radio = $( 'input[name=common_adverse]:checked' );
        $( "#adverse-label" ).val( radio.parent().text() );
        $( "#adverse-iri" ).val( radio.val() );
        $( "#adverse-label-display" ).text( 'Selected: ' + radio.parent().text() );
        $( "#adverse-iri-display" ).text( radio.val() );
        $( "#adverse-iri-display" ).attr( 'href', radio.val() );
        var url = 'http://ontobee.org/api/infobox?o=OAE&iri=' + $("#adverse-iri").val();
		$.getJSON( url, function( response ) {
			data = $.parseJSON( response );
			//console.log( data );
			$( "#adverse-definition-display" ).text( data.definition );
		});
    } );
});

</script>


<h3 class="head3_darkred">Ontobee Lookup</h3>

<table>
<td style="width:45%">
<div style="display:inline-block;font-size:20px" >

<div class="common" style="min-height:10vh">
<h3 style="text-align:left">Option 1. Select Term by Keyword Search:</h3>
<div class="ui-widget">
    <label for="keywords">Keywords: </label>
    <input id="keywords" size="30"/>
</div>
</div>

<div class="common" style="min-height:10vh">
<h3 style="text-align:left">Option 2. Select Commonly Identified Adverse Events:</h3> 
    <label><input name="common_adverse" class="common_adverse" type="radio" value="http://purl.obolibrary.org/obo/OAE_0000374">Pain</label>
    <label><input name="common_adverse" class="common_adverse" type="radio" value="http://purl.obolibrary.org/obo/OAE_0000362">Rash</label>
    <label><input name="common_adverse" class="common_adverse" type="radio" value="http://purl.obolibrary.org/obo/OAE_0000556">Swelling</label>
    <label><input name="common_adverse" class="common_adverse" type="radio" value="http://purl.obolibrary.org/obo/OAE_0000373">Itching</label>
</div>

<div class="common">
<h3 style="text-align:left">Option 3. Select term by choosing:</h3>
<div style="height:40vh;overflow-y:scroll">
<div id="tree" style="display:none;border:none">
<ul>
<?php

print_r($term->hierarchy);
function printIndexTree( $tree ) {
    foreach( $tree as $branch ) {
        $iri = $branch['iri'];
        $label = $branch['label'];
        $children = $branch['children'];
        if ( !empty( $children ) ) {
            echo printBranch( $iri, $label );
            echo "<ul>";
            printIndexTree( $children );
            echo "</ul>";
        } else {
            echo printLeave( $iri, $label );
        }
    }
}

function printLeave( $iri, $label ) {
    $html =
<<<END
<li id='$iri'><label><input name="common_adverse" class="common_adverse" type="radio" value="$iri">$label</label>
END;
    return $html;
}

function printBranch( $iri, $label ) {
    $html =
<<<END
<li class='folder' id='$iri'><label><input name="common_adverse" class="common_adverse" type="radio" value="$iri">$label</label>
END;
    return $html;
}

echo printIndexTree( $tree );

?>
</ul>
</div>
</div>
</div>

</td>

<td style="width:45%;padding:20px">

<div class="common" style="min-height:70vh;over-flow:auto;border:1px solid black">

<div style="display:inline-block;min-height:20px;font-size:20px" >
<strong>
<label id="adverse-label-display"></label>
</strong>
<br>
<br>
<a id="adverse-iri-display"></a>
<br>
<br>
<label id="adverse-definition-display"></label>
<input id="adverse-label" type="hidden" />
<input id="adverse-iri" type="hidden" />
</div>

</div>

</td>

</table>

</div>

<?php require TEMPLATE . 'footer.default.dwt.php'; ?>