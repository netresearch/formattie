<?php header('Content-Type: application/xhtml+xml'); ?><?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title>formattie</title>
 </head>
 <body>
  <form action="/" method="POST">
   <a href="/" target="_blank">new tab</a><br/>
   <textarea id="content" name="content" rows="15" cols="80"><![CDATA[<?php
if (isset($_POST['content'])) {
    $_POST['content'] = ltrim($_POST['content']);
    echo $_POST['content'];
}
?>]]></textarea>
   <br/>
   <input type="submit" value="Submit"/>
   <input type="button" value="Clear" onclick="javascript:document.getElementById('content').value='';"/>
   <label><input type="checkbox" name="fixJsonEscaping"/> Fix JSON escaping</label>
  </form>
<?php
    if (isset($_POST['content'])) {
        $content = $_POST['content'];
        if (isset($_POST['fixJsonEscaping'])) {
            $content = str_replace(
                array('\n', '\\"', '\\/'),
                array("\n", '"', '/'),
                $content
            );
        }
        if (is_numeric($content) && strlen(trim($content)) <= 10) {
            //unix timestamp
            $content = trim($content);
            $nice = 'UTC:   ' . gmdate('c', $content) . "\n"
                . 'Local: ' . date('c', $content);

            echo '<table>'
                . '<caption>Unix timestamp</caption>'
                . '<tr><th colspan="2" align="left">Timestamp</th>'
                . '<td><tt>' . $content . '</tt></td></tr>'
                . '<tr><th colspan="2" align="left">UTC date</th>'
                . '<td><tt>' . gmdate('c', $content) . '</tt></td></tr>'
                . '<tr><th>Local date</th>'
                . '<td>' . date('T P, e') . '</td>'
                . '<td><tt>' . date('c', $content) . '</tt></td></tr>'
            . '</table>';
        } else if (strpos(substr($content, 0, 10), '://') !== false
            || substr($content, 0, 7) == 'mailto:'
        ) {
            //URL
            $parts = parse_url($content);
            if (isset($parts['path'])) {
                $parts['path'] = urldecode($parts['path']);
            }
            if (isset($parts['query'])) {
                parse_str($parts['query'], $queryparts);
                $parts['query'] = $queryparts;
            }
            $nice = var_export($parts, true);
            echo '<pre>' . htmlspecialchars($nice) . '</pre>';
        } else if ($content{0} == '{' || $content{0} == '[') {
            //json
            $data = json_decode($content);
            if ($data === null) {
                // Define the errors.
                $constants = get_defined_constants(true);
                $json_errors = array();
                foreach ($constants["json"] as $name => $value) {
                    if (!strncmp($name, "JSON_ERROR_", 11)) {
                        $json_errors[$value] = $name;
                    }
                }
                echo '<p class="error">JSON error: ' . $json_errors[json_last_error()] . '</p>';
            }
            $nice = json_encode($data, JSON_PRETTY_PRINT);
            echo '<h2>PHP object</h2>';
            echo '<pre>' . htmlspecialchars(var_export($data, true)) . '</pre>';
            echo '<h2>Pretty JSON</h2>';
            echo '<pre>' . htmlspecialchars($nice) . '</pre>';
        } else if (strpos(substr($content, 0, 64), ':{') !== false) {
            //serialized php variable
            $nice = var_export(unserialize($content), true);
        } else {
            if (strpos(substr($content, 0, 60), 'version=\"1.0\"') !== false) {
                //escaped string copied from e.g. firebug
                $content = str_replace(
                    array('\"', '\/', '\n'),
                    array('"', '/', "\n"),
                    $content
                );
            }
            //xml
            $descriptorspec = array(
                0 => array('pipe', 'r'),//stdin
                1 => array('pipe', 'w'),//stdout
                2 => array('pipe', 'w') //stderr
            );
            $process = proc_open('xmllint --recover --format -', $descriptorspec, $pipes);
            if (!is_resource($process)) {
                die(
                    '<div class="alert alert-error">'
                    . 'Cannot open process to execute xmllint'
                    . '</div>'
                );
            }

            fwrite($pipes[0], $content);
            fclose($pipes[0]);

            $nice = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $retval = proc_close($process);

            if ($retval != 0) {
                echo '<div style="border:1px solid red;">Error> ' . htmlspecialchars($errors) . '</div>';
            }

            require_once 'MediaWiki/geshi/geshi/geshi.php';
            $geshi = new \GeSHi($nice, 'xml');
            //$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
            //$geshi->set_header_type(GESHI_HEADER_DIV);

            echo '<pre>' . $geshi->parse_code() . '</pre>';
            echo 'Size: ' . number_format(strlen($content) / 1024, 2) . 'kiB';
        }

        echo '<hr/>';
        echo '<textarea rows="10" cols="80"><![CDATA[' . $nice . ']]></textarea>';
    }
?>
  <script type="text/javascript">
    var content = document.getElementById('content');
    content.focus();
    if (content.value == '') {
        document.execCommand('paste');
    }
  </script>
 </body>
</html>
