function swap_content( span ) {
	displayType = ( document.getElementById( span ).style.display == 'none' ) ? '' : 'none';
	document.getElementById( span ).style.display = displayType;
}
