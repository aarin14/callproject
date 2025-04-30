<?php
require_once "auth.php";
require_once "utils.php";

// AssemblyAI API configuration
$assemblyai_api_key = '';
$env_file = __DIR__ . '/.env';

if (file_exists($env_file)) {
    $env_contents = file_get_contents($env_file);
    if (preg_match('/ASSEMBLYAI_API_KEY=(.*)/', $env_contents, $matches)) {
        $assemblyai_api_key = trim($matches[1]);
        write_log("API key found in .env file");
    }
}

if (empty($assemblyai_api_key)) {
    $assemblyai_api_key = getenv('ASSEMBLYAI_API_KEY');
    if (!empty($assemblyai_api_key)) {
        write_log("API key found in environment variables");
    }
}

if (empty($assemblyai_api_key)) {
    write_log("Warning: ASSEMBLYAI_API_KEY not found in .env file or environment variables");
    write_log("Current environment: " . print_r($_ENV, true));
    write_log("Env file contents: " . (file_exists($env_file) ? file_get_contents($env_file) : 'File not found'));
}

$assemblyai_endpoint = "https://api.assemblyai.com/v2/transcript";
$assemblyai_upload_endpoint = "https://api.assemblyai.com/v2/upload";

function upload_audio_to_assemblyai($audio_file_path) {
    global $assemblyai_api_key;
    
    write_log("=== Starting AssemblyAI Upload ===");
    write_log("Audio file path: " . $audio_file_path);
    
    if (empty($assemblyai_api_key)) {
        write_log("Error: AssemblyAI API key is not configured");
        throw new Exception("AssemblyAI API key is not configured. Please set the ASSEMBLYAI_API_KEY environment variable.");
    }
    
    if (!file_exists($audio_file_path)) {
        write_log("Error: Audio file does not exist at path: " . $audio_file_path);
        throw new Exception("Audio file not found");
    }
    
    // Get file extension and set appropriate MIME type
    $extension = strtolower(pathinfo($audio_file_path, PATHINFO_EXTENSION));
    $mime_types = [
        'wav' => 'audio/wav',
        'mp3' => 'audio/mpeg',
        'm4a' => 'audio/mp4',
        'ogg' => 'audio/ogg',
        'flac' => 'audio/flac'
    ];
    
    $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
    write_log("Detected MIME type: " . $mime_type);
    
    // First, upload the file to AssemblyAI's storage
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.assemblyai.com/v2/upload");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "authorization: " . $assemblyai_api_key,
        "content-type: " . $mime_type
    ]);
    
    // Read the file content
    $file_content = file_get_contents($audio_file_path);
    if ($file_content === false) {
        throw new Exception("Failed to read audio file");
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    write_log("Sending file to AssemblyAI upload endpoint...");
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        write_log("cURL Error: " . $error);
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        write_log("Verbose log: " . $verboseLog);
        throw new Exception("Upload Error: " . $error);
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    write_log("HTTP Response Code: " . $http_code);
    write_log("Raw Response: " . $response);
    
    $upload_response = json_decode($response, true);
    curl_close($ch);
    
    if (!isset($upload_response['upload_url'])) {
        write_log("Invalid upload response: " . $response);
        throw new Exception("Failed to get upload URL: " . $response);
    }
    
    write_log("Upload URL received: " . $upload_response['upload_url']);
    
    // Now submit the transcription job
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.assemblyai.com/v2/transcript");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "authorization: " . $assemblyai_api_key,
        "content-type: application/json"
    ]);
    
    $request_body = json_encode([
        "audio_url" => $upload_response['upload_url']
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    write_log("Starting transcription job...");
    write_log("Request body: " . $request_body);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        write_log("cURL Error: " . $error);
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        write_log("Verbose log: " . $verboseLog);
        throw new Exception("Transcription Error: " . $error);
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    write_log("HTTP Response Code: " . $http_code);
    write_log("Raw Response: " . $response);
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        write_log("JSON decode error: " . json_last_error_msg());
        throw new Exception("Invalid JSON response from AssemblyAI: " . $response);
    }
    
    return $result;
}

function get_transcription_status($transcript_id) {
    global $assemblyai_api_key;
    
    write_log("=== Checking Transcription Status ===");
    write_log("Transcript ID: " . $transcript_id);
    
    if (empty($assemblyai_api_key)) {
        write_log("Error: AssemblyAI API key is not configured");
        throw new Exception("AssemblyAI API key is not configured. Please set the ASSEMBLYAI_API_KEY environment variable.");
    }
    
    $max_attempts = 30; // Maximum number of polling attempts
    $attempt = 0;
    $wait_time = 2; // Wait 2 seconds between attempts
    
    while ($attempt < $max_attempts) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.assemblyai.com/v2/transcript/" . $transcript_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        $headers = array(
            "authorization: " . $assemblyai_api_key
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        rewind($verbose);
        $verbose_log = stream_get_contents($verbose);
        write_log("cURL verbose output: " . $verbose_log);
        
        if (curl_errno($ch)) {
            write_log("cURL Error: " . curl_error($ch));
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        
        write_log("HTTP Response Code: " . $http_code);
        write_log("Response: " . $response);
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            write_log("JSON decode error: " . json_last_error_msg());
            throw new Exception("Invalid JSON response from AssemblyAI");
        }
        
        // Check transcription status
        if (isset($result['status'])) {
            if ($result['status'] === 'completed') {
                write_log("Transcription completed successfully");
                return $result;
            } else if ($result['status'] === 'error') {
                write_log("Transcription failed: " . ($result['error'] ?? 'Unknown error'));
                throw new Exception("Transcription failed: " . ($result['error'] ?? 'Unknown error'));
            }
        }
        
        $attempt++;
        write_log("Attempt $attempt: Transcription still processing. Waiting $wait_time seconds...");
        sleep($wait_time);
    }
    
    write_log("Maximum polling attempts reached. Transcription may still be processing.");
    throw new Exception("Transcription timed out. Please try again later.");
}
?> 