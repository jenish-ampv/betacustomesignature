<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');
class CIT_CRON
{

	public function __construct()
	{
		$GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');
	}

	public function displayPage(){
        echo "<pre>";
        $import_items = $GLOBALS['DB']->query("SELECT * FROM signature_import_process_data WHERE `status`=0",array()); //status 0 = pending
		// $GLOBALS['total_imported_signature'] = sizeof($import_items);
		foreach ($import_items as $key => $import_item) {
			$signature_id = $import_item['signature_id'];
            $user_id = $import_item['user_id'];
    		$signatureData = $GLOBALS['DB']->row("SELECT * FROM signature WHERE `signature_id`=? ",array($signature_id));
    		// if(isset($signatureData['department_id'])){
    		// 	if($signatureData['department_id'] != $GLOBALS['current_department_id']){
    		// 		return false;
    		// 	}
    		// }

            $signatureHtml = '';
			$signatureHtml .= '<div class="signature_preview_container" data-signature-id="'.$signature_id.'">';
			$signatureHtml .= $GLOBALS['SIGNATURE']->getUserSignature($signature_id);
			$sigSaveHtmlUrl = GetUrl(array('module'=>'azuread','category_id'=>'saveSignatureHtml','id'=>$signature_id));
			$signatureHtml .= '</div>';
			$signatureHtml .= '<input type="hidden" id="signature_save_html_url_'.$signature_id.'" value="'.$sigSaveHtmlUrl.'" />';

            $signatureHtml = $this->getSignatureWithImage($signatureHtml, $user_id, $signature_id);
            $import_items = $GLOBALS['DB']->query("UPDATE signature SET outlook_html = ? WHERE signature_id = ? AND user_id = ?",array($signatureHtml, $signature_id, $user_id));
            $import_items = $GLOBALS['DB']->query("UPDATE signature_import_process_data SET status = 1 WHERE signature_id = ? AND user_id = ?",array($signature_id, $user_id));
		}
        echo "Singnature import process completed successfully.";
        return;
    }

    function getSignatureWithImage($html, $user_id, $signature_id){
        // echo $html;exit;
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // suppress parsing errors/warnings
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // find all <td> tags with class containing "imagetopngClass"
        $elements = $xpath->query('//td[contains(@class,"imagetopngClass")]');

        foreach ($elements as $index => $el) {
            $parent = $el->parentNode;
            if (!$parent) continue;
            
            $tdDimensions = $this->getButtonDimensions($el);

            // 🔍 Find the <a> tag inside this <td>
            $aTags = $el->getElementsByTagName('a');
            $href = null;
            if ($aTags->length > 0) {
                $href = $aTags->item(0)->getAttribute('href'); 
            }

            // ✅ Get button name
            $imageName = $el->getAttribute('data-image-name');

            // ✅ Ensure font-weight:bold is applied (like JS)
            $currentStyle = $el->getAttribute('style');
            if (stripos($currentStyle, 'font-weight') === false) {
                $currentStyle .= ($currentStyle ? '; ' : '') . 'font-weight:bold;';
                $el->setAttribute('style', $currentStyle);
            }

            // ✅ Handle border-radius logic (like JS)
            if (preg_match('/border-radius\s*:\s*([^;]+)/i', $currentStyle, $brMatch)) {
                $beforeBorderRadius = trim($brMatch[1]);

                if ($beforeBorderRadius === '200px') {
                    // Replace existing border-radius value with 15px
                    $currentStyle = preg_replace(
                        '/border-radius\s*:\s*[^;]+/i',
                        'border-radius:15px',
                        $currentStyle
                    );
                }
            }

            // Convert parent node to HTML
            $nodeHtml = $dom->saveHTML($parent);

            // Wrap into minimal HTML
            $wrappedHtml = <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    html, body {
                        margin: 0;
                        padding: 0;
                        background-color: rgba(0, 0, 0, 0) !important; /* Force transparent */
                        padding-top: 15px;
                    }
                    table {
                        border-collapse: collapse;
                        display: inline-table;
                    }
                </style>
            </head>
            <body>
                <table>{$nodeHtml}</table>
                <div style="height:1px; opacity:0;">&nbsp;</div>
            </body>
            </html>
            HTML;

            // Define output path for each image
            $outputPath = $_SERVER['DOCUMENT_ROOT'] . '/testhtmltoimage/testhtmltoimage_' . time() . "_{$index}.png";

            // Generate image for each HTML block
            $imagePath = $this->generateImageFromHtml($wrappedHtml, $outputPath, $imageName, $user_id, $signature_id);

            // ✅ Replace inner HTML of <td class="imagetopngClass"> with <img>
            while ($el->firstChild) {
                $el->removeChild($el->firstChild); // clear old content
            }

            $img = $dom->createElement('img');
            $img->setAttribute('src', $imagePath); // relative path
            $img->setAttribute('alt', 'Generated Image');
            $img->setAttribute('style', 'vertical-align: middle;');
            $img->setAttribute('width', $tdDimensions['width']);
            $img->setAttribute('height', $tdDimensions['height']);

            if(!empty($href)){
            // Create <a> and set href
                $a = $dom->createElement('a');
                if (!empty($href)) {
                    $a->setAttribute('href', $href);
                }
                $a->appendChild($img); // put <img> inside <a>
                $el->appendChild($a);
            }else{
                $el->appendChild($img);
            }


            // ✅ Remove style from imagetopngClass <td>
            $el->removeAttribute('style');
            $el->removeAttribute('bgcolor');
            
        }

        
        $containerNodes = $xpath->query('//div[contains(@class,"signature_preview_container")]');

        $updatedHtml = '';
        if ($containerNodes->length > 0) {
            // Get the first matching container
            $container = $containerNodes->item(0);

            // Save only its inner HTML
            $innerHtml = '';
            foreach ($container->childNodes as $child) {
                $innerHtml .= $dom->saveHTML($child);
            }

            $updatedHtml = $innerHtml;
        }
        // $updatedHtml = $dom->saveHTML();
        return $updatedHtml; // Output the modified HTML
        // exit;
    }

    function getButtonDimensions(DOMElement $el) {
        $style = $el->getAttribute('style');
        $text  = '';
        $iconWidth = 0;

        // 1️⃣ Extract text inside <span>
        $spans = $el->getElementsByTagName('span');
        if ($spans->length > 0) {
            $text = trim($spans->item(0)->textContent);
        }

        // 2️⃣ Extract icon <img> width
        $imgs = $el->getElementsByTagName('img');
        if ($imgs->length > 0) {
            $iconWidth = intval($imgs->item(0)->getAttribute('width'));
        }

        // 3️⃣ Extract padding from style (e.g. "4px 15px")
        $padding = 0;
        if (preg_match('/padding\s*:\s*([^;]+)/i', $style, $padMatch)) {
            $parts = preg_split('/\s+/', trim($padMatch[1]));
            if (count($parts) == 1) {
                $padding = (int)$parts[0] * 2; // top+bottom and left+right same
            } elseif (count($parts) == 2) {
                $padding = (int)$parts[1] * 2; // left+right only (since height handled separately)
            } elseif (count($parts) >= 4) {
                $padding = (int)$parts[1] + (int)$parts[3]; // left + right
            }
        }

        // 4️⃣ Estimate text width (assuming ~7px per char @ font-size 12px)
        $textWidth = mb_strlen($text) * 7;

        // 5️⃣ Calculate total width
        $width = $iconWidth + $textWidth + $padding;

        // 6️⃣ Height ~ line-height (from <span>) + vertical padding
        $lineHeight = 16; // fallback
        if (preg_match('/line-height\s*:\s*([^;]+)/i', $style, $lhMatch)) {
            $lineHeight = intval($lhMatch[1]);
        }
        $height = $lineHeight + 8; // +padding-top+bottom approx

        // 7️⃣ Border-radius (like JS)
        $borderRadius = null;
        if (preg_match('/border-radius\s*:\s*([^;]+)/i', $style, $radiusMatch)) {
            $borderRadius = trim($radiusMatch[1]);
        }

        return [
            'text' => $text,
            'width' => $width . 'px',
            'height' => $height+5 . 'px',
            'borderRadius' => $borderRadius,
        ];
    }

    function generateImageFromHtml($html, $outputPath, $imageName, $user_id, $signature_id) {
        // Save the HTML content to a temporary file
        $outputDir = dirname($outputPath);
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        $tempHtmlPath = dirname(path: $outputPath) . '/temp_' . uniqid() . '.html';

        if (file_put_contents($tempHtmlPath, $html) === false) {
            print_r([
                'success' => false,
                'message' => 'Failed to write HTML to temporary file',
                'debug' => []
            ]);exit;
        }

        // Create the image using wkhtmltoimage
        // $command = escapeshellcmd("wkhtmltoimage --transparent {$tempHtmlPath} {$outputPath}");
        // $command = escapeshellcmd("wkhtmltoimage --format png --transparent {$tempHtmlPath} {$outputPath}");
        // $command = escapeshellcmd("wkhtmltoimage --format png --zoom 3 --transparent {$tempHtmlPath} {$outputPath}");
        $command = escapeshellcmd("wkhtmltoimage --format png --transparent --zoom 3 --width 1800 {$tempHtmlPath} {$outputPath}");                          

        exec($command, $output, $result);

        $this->cropTransparentPaddingWithImagemagick($outputPath);
        // Clean up temp file
        // unlink($tempHtmlPath);

        if ($result !== 0 || !file_exists($outputPath)) {
            print_r([
                'success' => false,
                'message' => 'wkhtmltoimage failed',
                'debug' => $output
            ]);exit;
        }

        $base64Image = base64_encode(file_get_contents($outputPath));

        unlink($outputPath); // Optionally remove the output file after encoding
        unlink($tempHtmlPath); // Clean up temp HTML file
        // $outputPath = 'data:image/png;base64,' . $base64Image;
        return $this->uploadImageToAWS([
            'success' => true,
            'path' => $outputPath,
            'base64' => 'data:image/png;base64,' . $base64Image
        ], $imageName, $user_id, $signature_id);
    }

    function uploadImageToAWS($imageData, $imageName, $user_id, $signatureId){
        $data = $imageData['success'] ? $imageData['base64'] : null;
        if(is_null($data)){
            $return_arr = array("error" =>1, "msg"=>"Image conversion issue.");
            print_r($return_arr); exit;
        }
        $uploadLink = $_SERVER['DOCUMENT_ROOT'].'/upload-beta'; // image uploaded path 

        $imageName = !empty($imageName) ? $imageName.'.png' : 'signature_'.time().'.png'; // image name

        // below if condition added for manage htmltoimage with API call
        $userId = 'api';
        if(!empty($user_id)){
            $userId = $user_id;
        }

        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $pathToUserIDFolder = $uploadLink."/htmltoimage/".$userId;
        if (!file_exists($pathToUserIDFolder)) {
            mkdir($pathToUserIDFolder, 0777, true);
        }

        $pathToImage = $uploadLink."/htmltoimage/".$userId."/".$signatureId."/";
        if (!file_exists($pathToImage)) {
            mkdir($pathToImage, 0777, true);
        }
        $myfile = fopen($pathToImage.$imageName, "w") or die("Unable to open file!");
        fwrite($myfile, $data);
        fclose($myfile);


        $location = $uploadLink."/htmltoimage/".$userId."/".$signatureId."/".$imageName;
        $result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
            'Bucket'=>$GLOBALS['BUCKETNAME'],
            'Key' =>  'upload-beta/htmltoimage/'.$userId.'/'.$signatureId.'/'.$imageName,
            'SourceFile' => $location,
            'StorageClass' => 'REDUCED_REDUNDANCY',
            'ACL'   => 'public-read'
        ));
        $imagePath = $GLOBALS['BUCKETBASEURL']."/upload-beta/htmltoimage/".$userId."/".$signatureId."/".$imageName; 
        $return_arr = array("error" =>0, "image_path"=> $imagePath);
        return $imagePath;
    }

    function cropTransparentPaddingWithImagemagick($imagePath) {
        $escapedPath = escapeshellarg($imagePath);

        // First: Trim transparent padding
        $trimCommand = "convert {$escapedPath} -alpha on -bordercolor none -border 1 -fuzz 0% -trim +repage {$escapedPath}";
        exec($trimCommand, $output1, $status1);

        if ($status1 !== 0) {
            echo "Error trimming image:\n";
            print_r($output1);
            exit;
        }

        // Then: Enlarge the image 3x
        $resizeCommand = "convert {$escapedPath} -resize 300% {$escapedPath}";
        exec($resizeCommand, $output2, $status2);

        if ($status2 !== 0) {
            echo "Error resizing image:\n";
            print_r($output2);
        }
    }
}