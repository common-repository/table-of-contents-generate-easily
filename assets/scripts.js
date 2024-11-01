function content_index_toggleToc()
{
	if( jQuery('#the-toc-body').is(':visible') ){
		document.getElementById("the-toc-body").style.display="none";
		document.getElementById("the-toc-togglelink").innerHTML='Show';
	}
	else{
		document.getElementById("the-toc-body").style.display="block";
		document.getElementById("the-toc-togglelink").innerHTML='Hide';
	}
}