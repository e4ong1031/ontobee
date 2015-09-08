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
 * @file faqs.php
 * @author Edison Ong
 * @since Sep 3, 2015
 * @comment 
 */

if (!$this) {
	exit(header('HTTP/1.0 403 Forbidden'));
}

?>

<?php require TEMPLATE . 'header.default.dwt.php'; ?>

<h3 class="head3_darkred">Frequently Asked Questions</h3>
<p><strong>1. What is Ontobee? </strong></p>
<p> Ontobee is a  linked dataa server that serves as linked data for dereferencing ontology terms. Ontobee dynamically dereferences ontology term URIs to both HTML and RDF files. Ontobee has been used as the default onotlogy linked data server for most OBO Foundry library ontologies. The Ontobee program also includes many ontologies that are not  listed in the OBO Foundry library. </p>
<p><strong>2. How was the name &quot;Ontobee&quot; given? </strong> </p>
<p>The first name of the program was &quot;VOBrowser&quot; or &quot;VOBrowse&quot; and located in http://www.violinet.org/vaccineontology/vobrowser. When the VO browser technique was used to generate an OBI browser prototype, Oliver gave the name &quot;Ontobrowser&quot; with the web URL: http://ontobrowser.hegroup.org/. After realizing its potential  use of the  technology for many other ontologies, Oliver changed the name to &quot;OntoBee&quot; in September, 2009. The giving of this name was inspired by the name of another ontology-based software program <a href="http://ontofox.hegroup.org/">OntoFox</a> which was also coined by  Oliver. Oliver thought it might be a good idea to give another short animal name. He     imagined a bee flying and hanging over a flower as a pollinator, and the flower could be an ontology or ontology term. Therefore, Oliver suggested the name  &quot;OntoBee&quot;. Allen also liked the name. The website for this OntoBee project was later changed to: http://ontobee.hegroup.org/.  Oliver registered the ontobee.org domain name on May 18, 2010. The  project website then  became http://www.ontobee.org.  
<p><strong>3. How are the ontologies stored and queried in Ontobee? </strong>
<p> The  ontologies listed in Ontobee are primarily retrieved from the OBO Foundry library. Ontobee also maintains a small number of other ontologies based on needs and requests. These ontologies are processed into RDF triples and  then uploaded to the Hegroup Virtuoso RDF triple store. Upon a user's query request, Ontobee then executes SPARQL query scripts to query the results from the RDF triple store, process the results, and present the results in RDF format as the source and in HTML format to a web browser. </p>
<p><strong>4. What is the main feature of Ontobee? </strong></p>
<p>Based on the XSLT and SPARQL technologies, Ontobee achieves the following feature: for each ontology term, Ontobee  returns a source page in the RDF format, which supports the Semantic Web and Linked (Open) Data applications. In addition, Ontobee acts as a web-based browser of the ontology term information in HTML format. Ontobee provides query and visualization of ontology terms and ontology hierarchy. In addition, as a default linked data server for many ontologies, the ontology URIs from these ontologies are automatically redirected to Ontobee. See more information in Ontobee <a href="<?php echo SITEURL; ?>tutorial">Tutorial</a> and <a href="<?php echo SITEURL; ?>history">History</a>. </p>
<p><strong>5. What are the differences between the HTML page and the RDF source file for each ontology term, both generated by Ontobee? </strong></p>
<p>This OWL (RDF/XML) format is the default page format for the term URI. The HTML file is embedded in a XSL stylesheet introduced in the beginning of  the OWL file. When the term URI was visited by using a web browser, both the OWL file and the HTML content will be retrieved but only the HTML content will be shown in the browser for easy reading. A user can easily access the OWL (RDF/XML) content by check the source code of the  web page for an ontology term. Alternatively, the OWL output file can be retrieved by a web application program, or a Semantic Web system. This approach does not need a human visit to the HTML web page. </p>
<p>To receive the RDF file dereferncing  an ontology term URI from a (Linux) terminal, you can use a command like this: 
  <blockquote><span class="style1">curl -L --header &quot;Accept: application/rdf+xml&quot; 'http://purl.obolibrary.org/obo/VO_0000002'</span></blockquote>
  or directly: 
  <blockquote><span class="style1">curl -L  http://purl.obolibrary.org/obo/VO_0000002</span></blockquote>
  <p><em><strong>Note</strong></em>: If the server reports that the requested page has moved to a different location (indicated with a Location: header and a 3XX response code), the  option &quot;-L&quot; will make curl redo the request on the new place.  See more inforamtion about the command curl: <a href="http://curl.haxx.se/docs/manpage.html">http://curl.haxx.se/docs/manpage.html</a>.   </p>
  </p>
  <p><strong>6. How ontology term URIs with the OBO PURL domain are redirected to Ontobee? </strong> </p>
  <p>The OBO Foundry Operations Committee has prepared  a wonderful web page to introduce  how it works. The website is titled OBOPURLDomain: <a href="https://code.google.com/p/obo-foundry-operations-committee/wiki/OBOPURLDomain">https://code.google.com/p/obo-foundry-operations-committee/wiki/OBOPURLDomain</a>.
  <p><strong>7. Why is   ontology term URI dereferencing to HTML and RDF  important?</strong></p>
  <p>The <a href="http://en.wikipedia.org/wiki/Linked_data">Linked Data</a> (LD) is a method of publishing structured data so that it can be interlinked and become more useful. Dr. Tim  Berners-Lee, the inventor of the World Wide Web, outlined <a href="http://www.w3.org/DesignIssues/LinkedData.html">four LD rules</a>: (i) <em>Use URIs to identify things</em>; (ii) <em>Use URIs of HyperText  Transfer Protocol (HTTP) so that these things can be referred to and looked up  (&quot;dereferenced&quot;) by people and user agents</em>; (iii)<em> Provide useful information  about the thing when its  uniform resource identifier (URI) is dereferenced, using standard formats such as the  Resource Description Framework (RDF)</em>; and (iv)<em> Include links to other, related  URIs in the exposed data to improve discovery of other related information on  the Web</em>. </p>
  <p>To support the LD movement, it  is required to dereference ontology term URIs. The URI dereferencing represents the act of retrieving a representation of a resource identified by a URI. However, how to present the meaning of ontology terms by its URI  was a challenge  in 2009. For example, the Vaccine Ontology (VO) term  URIs were initially not dereferencable since they did not point to real web pages dedicated for the individual URIs. When some URIs of an ontology pointed to specific pages, the pages shown were often in pure HTML  format or in OWL (RDF/XML) format which contained the whole ontology instead of  individual terms. In both cases these pages did not efficiently support the Semantic  Web and LD rules. This was the rationale why Ontobee was developed to dereference ontology term URIs to both HTML and RDF. </p>
  <p><strong>8. What are the design principles of the Ontobee program?</strong></p>
  <p>Ontobee has been developed by following specific design principles. Some specific requirements we are aiming to address with Ontobee are:</p>
<ul>
  <li> Document and provide a consistent response for each OBO Library ontology.</li>
  <li> Linked Data Browsing</li>
  <li>Per term RDF-XML is valid OWL-DL.</li>
  <li> Includes an import of the full ontology, so the term is clearly defined. LOD browsers can choose whether or not to pay attention to the import.</li>
  <li>All necessary labels included.</li>
  <li> Attribution given, when available.</li>
  <li>No content negotiation - content negotiation confuses the identity of the resource. We prefer different URIs for different encodings.</li>
  <li>Conformance to httpRange-14 or chosen successor.</li>
  <li> Display of ontology developer html in addition, where developers have page-per-term resources.</li>
  <li> Links to or inclusion of additional html material.<br>
  </li>
</ul>
<p>It is noted that this list of requirements is  being revisited and updated. </p>
<p><strong>9. What is Ontobeest?</strong></p>
<p>Ontobeest is an Ontobee statistics tool. It  extracts  and displays detailed statistics for each ontology listed in Ontobee. Ontobeest is able to count the statistical numbers of classes, annotation properties, object properties, datatype properties owned by an ontology or imported from other ontologies to this ontology. The home page of Ontobeest (<a href="<?php echo SITEURL; ?>ontostat">http://www.ontobee.org/ontostat)</a> contains a summary table of all the terms shown in Ontobee. The web page of detailed statistics information for an ontology can be opened by clicking on "Detailed Statistics" on the ontology home page such as the <a href="<?php echo SITEURL; ?>ontology/ICO">ICO webpage</a>. This program was primarily developed by Bin Zhao in  He group, and named by Oliver as &quot;Ontobeest&quot; on March 24, 2014. A tutorial of Ontobeest is available here:  <a href="<?php echo SITEURL; ?>tutorial/#ontobeest">http://www.ontobee.org/tutorial/#ontobeest</a>. </p>
<p><strong>10. What is Ontobeep? </strong></p>
<p> <a href="<?php echo SITEURL; ?>ontobeep">Ontobeep</a> is an ontology alignment and comparison program that is developed based on the basic Ontobee function of serving and browsing linked data for ontology terms. Ontobeep retrieves selected ontologies using SPARQL and displays them in a single ontological hierarchy structure.  Shared and unique ontology terms among different ontologies are also calculated. A statistics page is  provided for users to take a careful review. This tool can also be used for ontology debugging purpose since it can identify many ontology errors easily. See more in Ontobeep tutorial: <a href="<?php echo SITEURL; ?>tutorial/ontobeep">http://www.ontobee.org/tutorial/ontobeep</a>. </p>
<p><strong>11. In some rare case, my Firefox was crashed when I clicked some ontology term in Ontobee? </strong></p>
<p>This scenario typically does not happen. If it occurs, it  most likely occurs when this page is too large with various reasons. We are seeking ways to solve this problem. It does not occur in Internet Explorer. So if it happens, you can try to use Internet Explorer. If it still happens, please let us know.</p>
<p><strong>12. Can Google find (or index) the ontology term results collected in Ontobee? </strong></p>
<p>Empirical  observations suggest that the answer is  yes. For example, when you search in Google for &quot;<a href="https://www.google.com/#q=colobus+fibroblast+cell+line">colobus fibroblast cell line</a>&quot; (without quotes), the Ontobee CLO terms &quot;immortal Colobus guereza skin-derived fibroblast cell line cell&quot; (<a href="<?php echo SITEURL; ?>ontology/?o=CLO&iri=http://purl.obolibrary.org/obo/CLO_0000421">CLO_0000421</a>)  will typically come to the top  of your Google search. When you search for <a href="https://www.google.com/#q=&quot;mineral+salt+vaccine+adjuvant&quot;">&quot;mineral salt vaccine adjuvant&quot;</a> (with quotes) in Google, the corresponding Ontobee VO term (<a href="<?php echo SITEURL; ?>ontology/?o=VO&iri=http://purl.obolibrary.org/obo/VO_0000093">VO_0000093</a>) also likely shows up as one of the top Google search hits.  </p>
<p><strong>13</strong>.<strong> Does the character length matter when I perform a keyword search using the Ontobee keyword search program?</strong></p>
<p>Yes. The Ontobee keyword search function uses auto-complete feature. It works well when the search keyword are four or more characters long. For example, when you search for &quot;gene&quot;, it will show up many terms that contains the characters &quot;gene&quot; in the word. In this case, those keywords that starts with teh character 'gene' will appear first. Such a search turns to be fast. However, when we search a keyword that is 3 characters or less (e.g., FCS, sex), the Ontobee search program relies on a different program and may act slower. In this case, if you can first specify which ontology to search, it would make your searching much faster. See more information on the  <a href="https://github.com/ontoden/ontobee/issues/37">github issue (#37)</a>. </p>
<p><strong>14. How can I contact the Ontobee development team to  make requests or ask questions?  </strong></p>
<p>Please refer to the <a href="<?php echo SITEURL; ?>contactus">Contact Us</a> page for information about contact. </p>
<p>&nbsp;</p>

<?php require TEMPLATE . 'footer.default.dwt.php'; ?>