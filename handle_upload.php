<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php'; // Composer autoloader
require_once "auth.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
if (file_exists(__DIR__ . '/.env')) {
    $dotenv->load();
}

$status = "error";
$responseMessage = "";
$sentimentResults = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transcribed_text'])) {
    $caller = htmlspecialchars(filter_input(INPUT_POST, 'caller', FILTER_DEFAULT) ?: '');
    $inputText = htmlspecialchars($_POST['transcribed_text']);

    if (empty($inputText)) {
        $responseMessage = "Please enter some text for sentiment analysis.";
    } else {
        try {
            // Path to Python script and virtual environment binary
            $pythonScript = __DIR__ . '/scripts/sentiment_vader.py';
            $pythonBin = __DIR__ . '/venv/bin/python3'; // Use virtual environment Python

            // Verify Python binary exists
            if (!file_exists($pythonBin)) {
                $responseMessage = "Error: Python binary not found at $pythonBin.";
                error_log("Python binary not found: $pythonBin");
                throw new Exception("Python binary not found");
            }

            // Verify Python script exists
            if (!file_exists($pythonScript)) {
                $responseMessage = "Error: Python script not found at $pythonScript.";
                error_log("Python script not found: $pythonScript");
                throw new Exception("Python script not found");
            }

            // Escape input text for safe command-line execution
            $escapedText = escapeshellarg($inputText);

            // Execute Python script
            $command = "$pythonBin $pythonScript $escapedText 2>&1";
            $output = [];
            $exitCode = 0;
            exec($command, $output, $exitCode);

            // Log the command and raw output for debugging
            error_log("Executed command: $command");
            error_log("Raw output: " . implode(" ", $output));

            // Check for execution errors
            if ($exitCode !== 0) {
                $responseMessage = "Error executing sentiment analysis: " . implode(" ", $output);
                error_log("Python execution error: Exit code $exitCode, Output: " . implode(" ", $output));
            } else {
                // Parse JSON output
                $result = json_decode(implode("", $output), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $responseMessage = "Error: Invalid JSON response from sentiment analysis.";
                    error_log("JSON decode error: " . json_last_error_msg() . ", Output: " . implode("", $output));
                } elseif (isset($result['error'])) {
                    $responseMessage = "Sentiment analysis error: " . $result['error'];
                    error_log("VADER error: " . $result['error']);
                } elseif (isset($result['sentiment'])) {
                    $status = "success";
                    $responseMessage = "Sentiment analysis complete.";
                    $sentimentResults = [[
                        'text' => $result['text'],
                        'sentiment' => $result['sentiment'],
                        'confidence' => $result['confidence'] ?? null,
                        'start' => 0,
                        'end' => strlen($result['text']),
                    ]];

                    // Save to history
                    $historyFile = 'data/history.json';
                    $history = file_exists($historyFile) ? (json_decode(file_get_contents($historyFile), true) ?? []) : [];
                    $newEntry = [
                        'caller' => $caller,
                        'text' => $result['text'],
                        'sentiment' => $result['sentiment'],
                        'confidence' => $result['confidence'] ?? null,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'from_text_input_vader' => true,
                    ];
                    $history[] = $newEntry;
                    file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
                } else {
                    $responseMessage = "Error: Invalid sentiment analysis response.";
                    error_log("VADER response: " . implode("", $output));
                }
            }

        } catch (Exception $e) {
            $responseMessage = "Sentiment analysis error: " . $e->getMessage();
            error_log("General Sentiment Analysis Error: " . $e->getMessage());
        }
    }
} else {
    $responseMessage = "Invalid request.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentiment Analysis Result | Helpdesk AI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h1 class="text-2xl font-bold mb-4 text-center <?php echo $status === 'success' ? 'text-green-600' : 'text-red-600'; ?>">
            <?php echo $status === 'success' ? 'Analysis Complete' : 'Analysis Error'; ?>
        </h1>
        <p class="text-gray-700 text-center mb-6"><?php echo htmlspecialchars($responseMessage); ?></p>

        <?php if ($status === 'success' && !empty($sentimentResults)): ?>
            <h2 class="text-xl font-semibold mb-2 text-center">Sentiment Analysis Results:</h2>
            <?php foreach ($sentimentResults as $sentiment_result): ?>
                <div class="border rounded p-4 mb-2">
                    <p class="text-gray-800"><strong>Text:</strong> <?php echo htmlspecialchars($sentiment_result['text']); ?></p>
                    <p class="text-<?php echo strtolower($sentiment_result['sentiment']) === 'positive' ? 'green' : (strtolower($sentiment_result['sentiment']) === 'negative' ? 'red' : 'yellow'); ?>-600">
                        <strong>Sentiment:</strong> <?php echo htmlspecialchars(strtoupper($sentiment_result['sentiment'])); ?>
                    </p>
                    <?php if (isset($sentiment_result['confidence'])): ?>
                        <p class="text-gray-600"><strong>Confidence:</strong> <?php echo htmlspecialchars(round($sentiment_result['confidence'], 2)); ?></p>
                    <?php endif; ?>
                    <?php if (isset($sentiment_result['start']) && isset($sentiment_result['end'])): ?>
                        <p class="text-gray-600 text-sm"><strong>Timestamp:</strong> <?php echo htmlspecialchars($sentiment_result['start']); ?> - <?php echo htmlspecialchars($sentiment_result['end']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="flex gap-3 justify-center mt-6">
            <a href="upload.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">Analyze Another Text</a>
            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>