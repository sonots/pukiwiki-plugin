function showHide() {
	if (document.getElementById) {
	var disp2 = document.getElementById('toc').childNodes[3].style.display;

	if (disp2 == "none") {
	document.getElementById('toc').childNodes[3].style.display = "block";
	document.getElementById('toc').className= "show_list";
	document.getElementById('showhide').firstChild.nodeValue = "折り畳む";}
	else {
	document.getElementById('toc').childNodes[3].style.display = "none";
	document.getElementById('toc').className= "hide_list";
	document.getElementById('showhide').firstChild.nodeValue = "開ける";}
	return false;}
}