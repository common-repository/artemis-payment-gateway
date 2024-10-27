function cp_copy_text(v) {
	var copyText = document.getElementById(v);
	copyText.select();
	copyText.setSelectionRange(0, 99999);
	navigator.clipboard.writeText(copyText.value);
}