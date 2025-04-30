<?php
require_once "auth.php";
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Audio | Sentiment Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slideIn {
            animation: slideIn 0.5s ease-out both;
        }
        .recording {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .waveform {
            height: 100px;
            background: #f3f4f6;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans flex min-h-screen">
    <aside class="w-64 bg-gray-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-10">Sentiment Analyzer</h2>
            <nav class="space-y-4 text-sm">
                <a href="welcome.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📊 Dashboard</a>
                <a href="record_audio.php" class="flex items-center gap-2 px-3 py-2 rounded bg-gray-700">🎤 Record Audio</a>
                <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📝 Upload Audio</a>
                <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🕓 Call History</a>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
            </nav>
        </div>
        <div class="text-sm text-gray-300 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
    </aside>

    <main class="flex-1 p-6 md:p-10 space-y-10">
        <header>
            <h1 class="text-3xl font-bold mb-2 animate-slideIn">Record Audio</h1>
            <p class="text-gray-600 animate-slideIn">Record an audio clip to analyze its sentiment.</p>
        </header>

        <div class="bg-white rounded-xl shadow p-6 max-w-2xl animate-slideIn">
            <form id="audioForm" action="handle_upload.php" method="post" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="input_type" value="audio">
                <input type="hidden" name="audio_data" id="audioData">

                <div>
                    <label for="caller" class="block text-gray-700 mb-1">Caller Name (Optional)</label>
                    <input type="text" name="caller" id="caller" placeholder="Enter caller name" class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="space-y-4">
                    <div class="flex justify-center space-x-4">
                        <button type="button" id="startRecord" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded transition">
                            Start Recording
                        </button>
                        <button type="button" id="stopRecord" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded transition" disabled>
                            Stop Recording
                        </button>
                    </div>

                    <div id="recordingStatus" class="hidden">
                        <div class="flex items-center justify-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2 recording"></div>
                            <span class="text-red-500 font-semibold">Recording...</span>
                        </div>
                    </div>

                    <div class="waveform">
                        <canvas id="waveform"></canvas>
                    </div>

                    <div class="text-center">
                        <span id="recordingTime" class="text-2xl font-mono">00:00</span>
                    </div>

                    <div>
                        <audio id="audioPlayer" controls class="w-full" style="display: none;"></audio>
                    </div>
                </div>

                <div class="flex justify-center space-x-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition" disabled>
                        Analyze Recording
                    </button>
                    <a href="upload.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <footer class="text-center text-gray-500 text-xs pt-10">
            © 2025 Helpdesk AI · Secure processing in place.
        </footer>
    </main>

    <script>
        let mediaRecorder;
        let audioChunks = [];
        let startTime;
        let timerInterval;
        let audioContext;
        let analyser;
        let canvasContext;
        let animationId;

        // Initialize audio context and canvas
        function initAudioContext() {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 2048;
            
            const canvas = document.getElementById('waveform');
            canvasContext = canvas.getContext('2d');
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        }

        // Draw waveform
        function drawWaveform() {
            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            
            function draw() {
                animationId = requestAnimationFrame(draw);
                analyser.getByteTimeDomainData(dataArray);
                
                canvasContext.fillStyle = '#f3f4f6';
                canvasContext.fillRect(0, 0, canvasContext.canvas.width, canvasContext.canvas.height);
                
                canvasContext.lineWidth = 2;
                canvasContext.strokeStyle = '#3b82f6';
                canvasContext.beginPath();
                
                const sliceWidth = canvasContext.canvas.width * 1.0 / bufferLength;
                let x = 0;
                
                for(let i = 0; i < bufferLength; i++) {
                    const v = dataArray[i] / 128.0;
                    const y = v * canvasContext.canvas.height / 2;
                    
                    if(i === 0) {
                        canvasContext.moveTo(x, y);
                    } else {
                        canvasContext.lineTo(x, y);
                    }
                    
                    x += sliceWidth;
                }
                
                canvasContext.lineTo(canvasContext.canvas.width, canvasContext.canvas.height/2);
                canvasContext.stroke();
            }
            
            draw();
        }

        // Update recording time
        function updateTimer() {
            const currentTime = Date.now();
            const elapsedTime = Math.floor((currentTime - startTime) / 1000);
            const minutes = Math.floor(elapsedTime / 60).toString().padStart(2, '0');
            const seconds = (elapsedTime % 60).toString().padStart(2, '0');
            document.getElementById('recordingTime').textContent = `${minutes}:${seconds}`;
        }

        // Start recording
        document.getElementById('startRecord').addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                initAudioContext();
                
                const source = audioContext.createMediaStreamSource(stream);
                source.connect(analyser);
                
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                
                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };
                
                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    const audioPlayer = document.getElementById('audioPlayer');
                    
                    audioPlayer.src = audioUrl;
                    audioPlayer.style.display = 'block';
                    
                    // Convert blob to base64
                    const reader = new FileReader();
                    reader.readAsDataURL(audioBlob);
                    reader.onloadend = () => {
                        document.getElementById('audioData').value = reader.result;
                        document.querySelector('#audioForm button[type="submit"]').disabled = false;
                    };
                    
                    // Stop waveform animation
                    cancelAnimationFrame(animationId);
                };
                
                mediaRecorder.start();
                startTime = Date.now();
                timerInterval = setInterval(updateTimer, 1000);
                
                document.getElementById('startRecord').disabled = true;
                document.getElementById('stopRecord').disabled = false;
                document.getElementById('recordingStatus').classList.remove('hidden');
                
                drawWaveform();
            } catch (err) {
                console.error('Error accessing microphone:', err);
                alert('Error accessing microphone. Please ensure you have granted microphone permissions.');
            }
        });

        // Stop recording
        document.getElementById('stopRecord').addEventListener('click', () => {
            mediaRecorder.stop();
            clearInterval(timerInterval);
            
            document.getElementById('startRecord').disabled = false;
            document.getElementById('stopRecord').disabled = true;
            document.getElementById('recordingStatus').classList.add('hidden');
        });

        // Update form language
        document.getElementById('language').addEventListener('change', (e) => {
            document.getElementById('formLanguage').value = e.target.value;
        });

        // Initialize form language
        document.getElementById('formLanguage').value = document.getElementById('language').value;
    </script>
</body>
</html> 