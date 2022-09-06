<?php
function sanitizeFilename ($filename) {
	$dangerousCharacters = array(" ", '"', "'", "&", "/", "\\", "?", "#");

	return str_replace($dangerousCharacters, '_', $filename);
}