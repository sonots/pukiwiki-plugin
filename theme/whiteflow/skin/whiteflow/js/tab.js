function tab(id1) {
if (document.getElementById) {
if (id1 == "e1") {
	id2 = "e2"; id3 = "e3";}
else if (id1 == "e2"){
	id2 = "e1"; id3="e3";}
else if (id1 == "e3"){
	id2 = "e1"; id3="e2";}

document.getElementById(id1).style.display = "block";
document.getElementById(id2).style.display = "none";
document.getElementById(id3).style.display = "none";
return false;}
}