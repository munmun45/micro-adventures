<?php
/**
 * Gemini API Test Script - 2026 Updated Version
 * Tests Gemini 3 Flash Preview API
 */

require_once 'config.php';

// Configuration
$API_KEY = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : 'AIzaSyARp_0r1JcQOoNCWdjRRiE3jKLuc1dr0qo'; 
$PROMPT  = "Create a simple 1-day travel itinerary for Mumbai, Maharashtra. Include morning, afternoon, and evening activities.";

// API Endpoint (Gemini 3 Flash Preview - 2026 version)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $API_KEY;

// Data Preparation
$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $PROMPT]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 2048
    ]
];

// CURL Execution
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Gemini API Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 20px; margin: 20px 0; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 20px 0; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; border-radius: 4px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🧪 Gemini API Test Results</h1>
    <div class="info">
        <strong>API Key:</strong> <?php echo substr($API_KEY, 0, 10); ?>...<br>
        <strong>Model:</strong> gemini-3-flash-preview<br>
        <strong>Test Prompt:</strong> <?php echo htmlspecialchars($PROMPT); ?>
    </div>

    <?php if ($httpCode === 200): ?>
        <?php
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])):
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'];
        ?>
            <div class="success">
                <h2>✅ SUCCESS! API is Working!</h2>
                <p><strong>AI Generated Response:</strong></p>
                <div style="background: white; padding: 15px; border-radius: 5px; margin-top: 10px;">
                    <?php echo nl2br(htmlspecialchars($aiText)); ?>
                </div>
                
                <?php if (isset($result['usageMetadata'])): ?>
                    <p style="margin-top: 15px; font-size: 14px; color: #666;">
                        <strong>Token Usage:</strong>
                        Prompt: <?php echo $result['usageMetadata']['promptTokenCount'] ?? 0; ?> |
                        Response: <?php echo $result['usageMetadata']['candidatesTokenCount'] ?? 0; ?> |
                        Total: <?php echo $result['usageMetadata']['totalTokenCount'] ?? 0; ?>
                    </p>
                <?php endif; ?>
                
                <hr style="margin: 20px 0;">
                <p><strong>🎉 Your travel-plan.php is now configured correctly!</strong></p>
                <p>The Gemini 3 Flash Preview API is working perfectly. You can now generate travel plans.</p>
            </div>
        <?php else: ?>
            <div class="error">
                <h2>⚠️ Unexpected Response Structure</h2>
                <p>Received a 200 response but couldn't find the expected text field.</p>
                <pre><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="error">
            <h2>❌ API Request Failed</h2>
            <p><strong>HTTP Status Code:</strong> <?php echo $httpCode; ?></p>
            
            <?php if ($httpCode == 429): ?>
                <p><strong>Error Type:</strong> Quota Exceeded</p>
                <p>Your API has reached its rate limit. Please wait a few minutes and try again, or check your quota at <a href="https://aistudio.google.com" target="_blank">Google AI Studio</a>.</p>
            <?php elseif ($httpCode == 403 || $httpCode == 401): ?>
                <p><strong>Error Type:</strong> Authentication Failed</p>
                <p>Your API key is invalid or doesn't have access. Get a new key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>.</p>
            <?php elseif ($httpCode == 404): ?>
                <p><strong>Error Type:</strong> Model Not Found</p>
                <p>The gemini-3-flash-preview model is not available for your API key. Try enabling the Generative Language API.</p>
            <?php endif; ?>
            
            <?php if ($curlError): ?>
                <p><strong>CURL Error:</strong> <?php echo htmlspecialchars($curlError); ?></p>
            <?php endif; ?>
            
            <p><strong>Raw Response:</strong></p>
            <pre><?php echo htmlspecialchars(substr($response, 0, 1000)); ?></pre>
        </div>
    <?php endif; ?>

    <div class="info">
        <h3>📚 Next Steps:</h3>
        <ul>
            <li>If successful ✅ - Your travel plan generator is ready to use!</li>
            <li>If error 429 ⚠️ - Wait and try again (quota limit)</li>
            <li>If error 403/401 ❌ - Get new API key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
            <li>If error 404 ❌ - Enable Generative Language API in Google Cloud Console</li>
        </ul>
    </div>
</body>
</html>