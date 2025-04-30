<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php'; // Composer autoloader
require_once "auth.php";
require_once "assemblyai.php";
require_once "utils.php";

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
try {
    $dotenv->load();
} catch (\Exception $e) {
    write_log("Error loading .env file: " . $e->getMessage());
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sentimentanalyser";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    write_log("Database connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

$caller = $_POST['caller'] ?? '';
$input_type = $_POST['input_type'] ?? 'text';
$transcribed_text = '';
$status = "error";
$responseMessage = "";
$sentimentResults = [];

// Get the language from POST data, default to English
$language = isset($_POST['language']) ? $_POST['language'] : 'en';

// Handle audio recording
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['audio_file'];
        $filename = $file['name'];
        $uploadPath = 'uploads/' . time() . '_' . $filename;
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Process the audio file
            $transcription_id = upload_audio_to_assemblyai($uploadPath, $language);
            
            if ($transcription_id) {
                $status = 'success';
                $responseMessage = 'Audio file uploaded successfully!';
                
                // Get transcription results
                write_log("Attempting to get transcription results for ID: " . $transcription_id['id']);
                $transcriptionResults = get_transcription_status($transcription_id['id']);
                
                if ($transcriptionResults) {
                    write_log("Transcription results received: " . json_encode($transcriptionResults));
                    if (isset($transcriptionResults['text'])) {
                        $transcribed_text = $transcriptionResults['text'];
                        // Perform sentiment analysis
                        $sentimentResults = analyze_sentiment($transcribed_text);
                    } else {
                        $status = 'error';
                        $responseMessage = 'Transcription completed but no text was returned.';
                        write_log("Error: Transcription completed but no text was returned. Full response: " . json_encode($transcriptionResults));
                    }
                } else {
                    $status = 'error';
                    $responseMessage = 'Failed to get transcription results. Please try again.';
                    write_log("Error: Failed to get transcription results for ID: " . $transcription_id['id']);
                }
            } else {
                $status = 'error';
                $responseMessage = 'Failed to process audio file. Please check the file format and try again.';
                write_log("Error: Failed to upload audio file to AssemblyAI");
            }
            
            // Clean up the temporary file
            unlink($uploadPath);
        } else {
            $status = 'error';
            $responseMessage = 'Failed to save uploaded file.';
        }
    } 
    // Handle recorded audio
    else if (isset($_POST['input_type']) && $_POST['input_type'] === 'audio' && isset($_POST['audio_data'])) {
        $audioData = $_POST['audio_data'];
        
        // Remove the data URL prefix
        $audioData = str_replace('data:audio/wav;base64,', '', $audioData);
        $audioData = str_replace(' ', '+', $audioData);
        
        // Decode the base64 data
        $audioData = base64_decode($audioData);
        
        // Generate a unique filename
        $filename = 'recording_' . time() . '.wav';
        $uploadPath = 'uploads/' . $filename;
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        // Save the audio file
        if (file_put_contents($uploadPath, $audioData)) {
            // Process the audio file
            $transcription_id = upload_audio_to_assemblyai($uploadPath, $language);
            
            if ($transcription_id) {
                $status = 'success';
                $responseMessage = 'Audio recording processed successfully!';
                
                // Get transcription results
                $transcriptionResults = get_transcription_status($transcription_id['id']);
                
                if ($transcriptionResults && isset($transcriptionResults['text'])) {
                    $transcribed_text = $transcriptionResults['text'];
                    // Perform sentiment analysis
                    $sentimentResults = analyze_sentiment($transcribed_text);
                } else {
                    $status = 'error';
                    $responseMessage = 'Failed to get transcription results.';
                }
            } else {
                $status = 'error';
                $responseMessage = 'Failed to process audio recording.';
            }
            
            // Clean up the temporary file
            unlink($uploadPath);
        } else {
            $status = 'error';
            $responseMessage = 'Failed to save audio recording.';
        }
    } else {
        $status = 'error';
        $responseMessage = 'Please select an audio file to upload or record audio.';
    }
} else {
    $transcribed_text = $_POST['transcribed_text'] ?? '';
    if (!empty($transcribed_text)) {
        $status = "success";
        $responseMessage = "Text received successfully.";
    }
}

// Only show the "No text to analyze" error if we haven't already set an error message
if (empty($transcribed_text) && $status !== 'error') {
    $status = "error";
    $responseMessage = "No text to analyze. Please provide either text or upload an audio file.";
}

// Process the text with your existing sentiment analysis logic
if ($status === "success" && !empty($transcribed_text)) {
    try {
        // Path to Python script and virtual environment binary
        $pythonScript = __DIR__ . '/scripts/sentiment_vader.py';
        $pythonBin = __DIR__ . '/venv/bin/python3';

        // Verify Python binary exists
        if (!file_exists($pythonBin)) {
            throw new Exception("Python binary not found at $pythonBin");
        }

        // Verify Python script exists
        if (!file_exists($pythonScript)) {
            throw new Exception("Python script not found at $pythonScript");
        }

        // Execute Python script
        $command = "$pythonBin $pythonScript " . escapeshellarg($transcribed_text) . " 2>&1";
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new Exception("Error executing sentiment analysis: " . implode(" ", $output));
        }

        $result = json_decode(implode("", $output), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from sentiment analysis");
        }

        if (isset($result['sentiment'])) {
            $sentimentResults = [[
                'text' => $result['text'],
                'sentiment' => $result['sentiment'],
                'confidence' => $result['confidence'] ?? null
            ]];

            // Store in database
            try {
                $stmt = $conn->prepare("INSERT INTO calls (caller, transcribed_text, sentiment, timestamp) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$caller, $transcribed_text, $result['sentiment']]);
            } catch(PDOException $e) {
                write_log("Database error: " . $e->getMessage());
            }
        } else {
            throw new Exception("Invalid sentiment analysis response");
        }
    } catch (Exception $e) {
        $status = "error";
        $responseMessage = "Error in sentiment analysis: " . $e->getMessage();
        write_log("Sentiment analysis error: " . $e->getMessage());
    }
}

function upload_to_assemblyai($file_path, $language = 'en') {
    $api_key = getenv('ASSEMBLYAI_API_KEY');
    if (!$api_key) {
        write_log("AssemblyAI API key not found");
        return false;
    }

    // Map language codes to AssemblyAI language codes
    $language_map = [
        'en' => 'en',
        'es' => 'es',
        'fr' => 'fr',
        'de' => 'de',
        'it' => 'it',
        'pt' => 'pt',
        'nl' => 'nl',
        'ja' => 'ja',
        'ko' => 'ko',
        'zh' => 'zh'
    ];

    $language_code = $language_map[$language] ?? 'en';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.assemblyai.com/v2/transcript');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'audio_url' => $file_path,
        'language_code' => $language_code
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $api_key,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $data = json_decode($response, true);
        return $data['id'] ?? false;
    }

    write_log("AssemblyAI upload failed with status code: " . $http_code);
    write_log("Response: " . $response);
    return false;
}

function analyze_sentiment($text) {
    try {
        // Path to Python script and virtual environment binary
        $pythonScript = __DIR__ . '/scripts/sentiment_vader.py';
        $pythonBin = __DIR__ . '/venv/bin/python3';

        // Verify Python binary exists
        if (!file_exists($pythonBin)) {
            throw new Exception("Python binary not found at $pythonBin");
        }

        // Verify Python script exists
        if (!file_exists($pythonScript)) {
            throw new Exception("Python script not found at $pythonScript");
        }

        // Execute Python script
        $command = "$pythonBin $pythonScript " . escapeshellarg($text) . " 2>&1";
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new Exception("Error executing sentiment analysis: " . implode(" ", $output));
        }

        $result = json_decode(implode("", $output), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from sentiment analysis");
        }

        if (isset($result['sentiment'])) {
            return [[
                'text' => $result['text'],
                'sentiment' => $result['sentiment'],
                'confidence' => $result['confidence'] ?? null
            ]];
        } else {
            throw new Exception("Invalid sentiment analysis response");
        }
    } catch (Exception $e) {
        write_log("Sentiment analysis error: " . $e->getMessage());
        return false;
    }
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
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="flex gap-3 justify-center mt-6">
            <a href="upload.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">Analyze Another Text</a>
            <a href="record_audio.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition">Back to Record Audio</a>
        </div>
    </div>
</body>
</html>