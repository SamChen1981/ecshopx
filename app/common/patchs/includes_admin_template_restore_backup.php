<?php
$template_content = preg_replace_callback($pattern, function ($r) use ($regions) {
    return "<!-- TemplateBeginEditable name=\"" . $r[1] . "\" -->\r\n" . $regions[$r[1]] . "\r\n<!-- TemplateEndEditable -->";
}, $template_content);
