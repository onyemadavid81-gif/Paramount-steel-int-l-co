<?php
// Paramount Steel RFQ mail handler
// This file must be uploaded as: public_html/send-rfq.php

ob_start();
header('Content-Type: application/json; charset=UTF-8');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function rfq_response($success, $message, $status = 200) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(
        array('success' => (bool)$success, 'message' => $message),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        rfq_response(false, 'This endpoint is working, but it only accepts form submissions.', 405);
    }

    if (!empty($_POST['website'])) {
        rfq_response(false, 'Spam submission detected.', 400);
    }

    $fullName = trim(isset($_POST['fullName']) ? $_POST['fullName'] : '');
    $companyName = trim(isset($_POST['companyName']) ? $_POST['companyName'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $deliveryLocation = trim(isset($_POST['deliveryLocation']) ? $_POST['deliveryLocation'] : '');
    $additionalNotes = trim(isset($_POST['additionalNotes']) ? $_POST['additionalNotes'] : '');

    if ($fullName === '') rfq_response(false, 'Please enter your full name.', 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) rfq_response(false, 'Please enter a valid email address.', 400);
    if ($phone === '') rfq_response(false, 'Please enter your phone number.', 400);
    if ($deliveryLocation === '') rfq_response(false, 'Please enter your delivery location.', 400);

    $products = isset($_POST['products']) && is_array($_POST['products']) ? $_POST['products'] : array();
    $productRowsHtml = '';
    $productRowsText = '';
    $number = 1;

    foreach ($products as $product) {
        if (!is_array($product)) continue;
        $category = trim(isset($product['category']) ? $product['category'] : '');
        $spec = trim(isset($product['spec']) ? $product['spec'] : '');
        $qty = trim(isset($product['qty']) ? $product['qty'] : '');
        if ($category === '' && $spec === '' && $qty === '') continue;

        $c = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
        $s = htmlspecialchars($spec, ENT_QUOTES, 'UTF-8');
        $q = htmlspecialchars($qty, ENT_QUOTES, 'UTF-8');
        $productRowsHtml .= '<tr><td style="padding:10px;border:1px solid #ddd">'.$number.'</td><td style="padding:10px;border:1px solid #ddd">'.$c.'</td><td style="padding:10px;border:1px solid #ddd">'.$s.'</td><td style="padding:10px;border:1px solid #ddd">'.$q.'</td></tr>';
        $productRowsText .= "Product {$number}\nCategory: {$category}\nSpecification: {$spec}\nQuantity: {$qty}\n\n";
        $number++;
    }

    if ($productRowsHtml === '') {
        $productRowsHtml = '<tr><td colspan="4" style="padding:10px;border:1px solid #ddd">No product details supplied.</td></tr>';
        $productRowsText = "No product details supplied.\n";
    }

    $to = 'info@paramountsteel.com.ng';
    $from = 'info@paramountsteel.com.ng';
    $subject = 'New RFQ Request - Paramount Steel - ' . $fullName;

    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $safeCompany = htmlspecialchars($companyName !== '' ? $companyName : 'Not provided', ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $safeLocation = htmlspecialchars($deliveryLocation, ENT_QUOTES, 'UTF-8');
    $safeNotes = nl2br(htmlspecialchars($additionalNotes !== '' ? $additionalNotes : 'No additional notes provided.', ENT_QUOTES, 'UTF-8'));

    $htmlBody = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px"><div style="max-width:800px;margin:auto;background:#fff;padding:30px"><h2 style="color:#0b1b3d">New Request for Quotation</h2><p>A new quotation request was submitted through the Paramount Steel website.</p><hr><h3 style="color:#008751">Customer Information</h3><table style="width:100%;border-collapse:collapse">' .
        '<tr><td style="padding:10px;border:1px solid #ddd;font-weight:bold">Full Name</td><td style="padding:10px;border:1px solid #ddd">'.$safeName.'</td></tr>' .
        '<tr><td style="padding:10px;border:1px solid #ddd;font-weight:bold">Company</td><td style="padding:10px;border:1px solid #ddd">'.$safeCompany.'</td></tr>' .
        '<tr><td style="padding:10px;border:1px solid #ddd;font-weight:bold">Email</td><td style="padding:10px;border:1px solid #ddd">'.$safeEmail.'</td></tr>' .
        '<tr><td style="padding:10px;border:1px solid #ddd;font-weight:bold">Phone</td><td style="padding:10px;border:1px solid #ddd">'.$safePhone.'</td></tr>' .
        '<tr><td style="padding:10px;border:1px solid #ddd;font-weight:bold">Delivery Location</td><td style="padding:10px;border:1px solid #ddd">'.$safeLocation.'</td></tr>' .
        '</table><h3 style="color:#008751;margin-top:30px">Requested Products</h3><table style="width:100%;border-collapse:collapse"><thead><tr style="background:#0b1b3d;color:#fff"><th style="padding:10px">#</th><th style="padding:10px">Category</th><th style="padding:10px">Specification</th><th style="padding:10px">Quantity</th></tr></thead><tbody>'.$productRowsHtml.'</tbody></table><h3 style="color:#008751;margin-top:30px">Additional Notes</h3><p>'.$safeNotes.'</p></div></body></html>';

    $textBody = "NEW REQUEST FOR QUOTATION\n\nCustomer Information\n----------------------\nFull Name: {$fullName}\nCompany: " . ($companyName !== '' ? $companyName : 'Not provided') . "\nEmail: {$email}\nPhone: {$phone}\nDelivery Location: {$deliveryLocation}\n\nRequested Products\n------------------\n{$productRowsText}\nAdditional Notes\n----------------\n" . ($additionalNotes !== '' ? $additionalNotes : 'No additional notes provided.') . "\n";

    $boundary = '=_RFQ_' . md5(uniqid('', true));
    $headers = "From: Paramount Steel Website <{$from}>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $message = '--'.$boundary."\r\n";
    $message .= 'Content-Type: multipart/alternative; boundary="ALT_'.$boundary.'"' . "\r\n\r\n";
    $message .= '--ALT_'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".$textBody."\r\n";
    $message .= '--ALT_'.$boundary."\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".$htmlBody."\r\n";
    $message .= '--ALT_'.$boundary."--\r\n";

    if (isset($_FILES['boqFile']) && isset($_FILES['boqFile']['error']) && $_FILES['boqFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['boqFile']['error'] !== UPLOAD_ERR_OK) rfq_response(false, 'The uploaded file could not be processed.', 400);
        if ((int)$_FILES['boqFile']['size'] > 10 * 1024 * 1024) rfq_response(false, 'The uploaded file is too large. Maximum size is 10MB.', 400);

        $original = basename($_FILES['boqFile']['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = array('pdf','doc','docx','xls','xlsx','png','jpg','jpeg');
        if (!in_array($ext, $allowed, true)) rfq_response(false, 'That file type is not allowed.', 400);

        $mimeMap = array(
            'pdf'=>'application/pdf', 'doc'=>'application/msword',
            'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'=>'application/vnd.ms-excel',
            'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png'=>'image/png', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg'
        );
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
        $data = file_get_contents($_FILES['boqFile']['tmp_name']);
        if ($data === false) rfq_response(false, 'The uploaded file could not be read.', 500);

        $message .= '--'.$boundary."\r\n";
        $message .= 'Content-Type: '.$mimeMap[$ext].'; name="'.$filename.'"' . "\r\n";
        $message .= 'Content-Disposition: attachment; filename="'.$filename.'"' . "\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($data));
        $message .= "\r\n";
    }

    $message .= '--'.$boundary."--\r\n";

    $sent = mail($to, $subject, $message, $headers);
    if (!$sent) rfq_response(false, 'The server could not send the email. Please check the hosting email configuration.', 500);

    rfq_response(true, 'Your quotation request has been sent successfully.');

} catch (Exception $e) {
    error_log('RFQ ERROR: '.$e->getMessage());
    rfq_response(false, 'A server error occurred while processing your request.', 500);
} catch (Throwable $e) {
    error_log('RFQ ERROR: '.$e->getMessage());
    rfq_response(false, 'A server error occurred while processing your request.', 500);
}
?>
