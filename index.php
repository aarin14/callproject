<?php
require_once "auth.php";
$isAdmin = $_SESSION['user'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Helpdesk AI Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen flex">

    <aside class="w-64 bg-gray-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-10">Sentiment Analyzer</h2>
            <nav class="space-y-4 text-sm">
                <a href="index.php" class="flex items-center gap-2 px-3 py-2 rounded bg-gray-700">📊 Dashboard</a>
                <a href="record_audio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🎤 Record Audio</a>
                <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📝 Upload Audio</a>
                <a href="messages.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">💬 Messages</a>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
            </nav>
        </div>
        <div class="text-sm text-gray-300 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
    </aside>

    <main class="flex-1 p-6 md:p-10 space-y-10 overflow-y-auto">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold">Welcome, <?= htmlspecialchars($_SESSION['user']) ?> 👋</h1>
                <p class="text-gray-500">Here's what's happening with customer calls today.</p>
            </div>
            <div class="flex gap-3 items-center">
                <input type="date" id="filterDate" class="rounded px-2 py-1 text-sm text-gray-800" />
                <?php if ($isAdmin): ?>
                <button id="clearAll" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm">Clear All</button>
                <?php endif; ?>
            </div>
        </header>

        <section class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow flex items-center gap-4">
                <div class="text-4xl">😊</div>
                <div>
                    <h3 class="text-lg font-semibold">Positive Calls</h3>
                    <p id="positive" class="text-3xl font-bold text-green-500 counter">0</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow flex items-center gap-4">
                <div class="text-4xl">😐</div>
                <div>
                    <h3 class="text-lg font-semibold">Neutral Calls</h3>
                    <p id="neutral" class="text-3xl font-bold text-yellow-500 counter">0</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow flex items-center gap-4">
                <div class="text-4xl">😠</div>
                <div>
                    <h3 class="text-lg font-semibold">Negative Calls</h3>
                    <p id="negative" class="text-3xl font-bold text-red-500 counter">0</p>
                </div>
            </div>
        </section>

        <section class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-xl font-semibold mb-4">Sentiment Over Time</h2>
            <canvas id="sentimentChart" class="h-64"></canvas>
        </section>

        <section class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-xl font-semibold mb-4">Recent Calls</h2>
            <input type="text" id="searchInput" placeholder="Search caller or transcript..." class="w-full mb-4 p-2 rounded bg-gray-100 text-sm">
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500 uppercase">
                        <tr>
                            <th class="py-2 px-4">Caller</th>
                            <th class="py-2 px-4">Transcript</th>
                            <th class="py-2 px-4">Sentiment</th>
                            <th class="py-2 px-4 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="recent-calls" class="text-gray-700"></tbody>
                </table>
            </div>
        </section>

        <footer class="text-center text-xs text-gray-400 mt-10">
            © 2025 Helpdesk AI · Built with ❤️ and fast support
        </footer>
    </main>

    <script>
        const updateCounter = (el, target) => {
            let count = 0;
            const step = () => {
                if (count < target) {
                    count += Math.ceil((target - count) / 10);
                    el.textContent = count;
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target;
                }
            };
            step();
        };

        fetch('data/history.json?nocache=' + new Date().getTime())
            .then(res => {
                if (!res.ok) {
                    console.error(`Error fetching history.json: ${res.status} ${res.statusText}`);
                    alert('Failed to load call history. Please check the server or contact support.');
                    return [];
                }
                return res.json();
            })
            .then(data => {
                if (!Array.isArray(data)) {
                    console.error('Error: history.json does not contain an array.', data);
                    alert('Invalid data format in history.json. Please contact support.');
                    return;
                }

                console.log('Raw Data:', JSON.stringify(data, null, 2)); // Debug: Log raw JSON data

                const recentCalls = document.getElementById('recent-calls');
                const searchInput = document.getElementById('searchInput');
                const filterDate = document.getElementById('filterDate');
                let pos = 0, neu = 0, neg = 0;

                data.forEach(call => {
                    // Normalize sentiment to match expected values
                    const sentiment = call.sentiment === 'POSITIVE' ? 'Positive' :
                                     call.sentiment === 'NEGATIVE' ? 'Negative' :
                                     call.sentiment === 'NEUTRAL' ? 'Neutral' : call.sentiment;

                    console.log('Processing call:', { ...call, normalizedSentiment: sentiment }); // Debug: Log each call with normalized sentiment

                    if (sentiment === 'Positive') pos++;
                    else if (sentiment === 'Neutral') neu++;
                    else if (sentiment === 'Negative') neg++;
                });
                console.log('Sentiment Counts:', { pos, neu, neg }); // Debug: Log final counts

                updateCounter(document.getElementById('positive'), pos);
                updateCounter(document.getElementById('neutral'), neu);
                updateCounter(document.getElementById('negative'), neg);

                const ctx = document.getElementById('sentimentChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.slice(-10).map(c => c.timestamp || 'Unknown'),
                        datasets: [
                            {
                                label: 'Positive',
                                borderColor: 'green',
                                data: data.map(c => (c.sentiment === 'POSITIVE' || c.sentiment === 'Positive') ? 1 : 0),
                                fill: false
                            },
                            {
                                label: 'Neutral',
                                borderColor: 'orange',
                                data: data.map(c => (c.sentiment === 'NEUTRAL' || c.sentiment === 'Neutral') ? 1 : 0),
                                fill: false
                            },
                            {
                                label: 'Negative',
                                borderColor: 'red',
                                data: data.map(c => (c.sentiment === 'NEGATIVE' || c.sentiment === 'Negative') ? 1 : 0),
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });

                const render = (filteredData) => {
                    recentCalls.innerHTML = '';
                    filteredData.slice().reverse().slice(0, 10).forEach(call => {
                        // Normalize sentiment and use text if transcript is missing
                        const sentiment = call.sentiment === 'POSITIVE' ? 'Positive' :
                                         call.sentiment === 'NEGATIVE' ? 'Negative' :
                                         call.sentiment === 'NEUTRAL' ? 'Neutral' : call.sentiment || 'Unknown';
                        const transcript = call.transcript || call.text || 'No transcript';

                        console.log('Rendering call:', { ...call, normalizedSentiment: sentiment, transcript }); // Debug: Log each rendered call

                        const tr = document.createElement('tr');
                        tr.className = 'border-b hover:bg-gray-100';
                        const sentimentColor = sentiment === 'Positive' ? 'text-green-500' :
                                              sentiment === 'Neutral' ? 'text-yellow-500' : 'text-red-500';
                        tr.innerHTML = `
                            <td class="px-4 py-3">${call.caller || 'Unknown'}</td>
                            <td class="px-4 py-3 truncate max-w-xs">${transcript}</td>
                            <td class="px-4 py-3 font-semibold ${sentimentColor}">${sentiment}</td>
                            <td class="px-4 py-3 text-right text-xs">${call.timestamp || 'Unknown'}</td>
                        `;
                        recentCalls.appendChild(tr);
                    });
                };

                const filterAndRender = () => {
                    const searchTerm = searchInput.value.toLowerCase();
                    const dateFilter = filterDate.value;
                    const filtered = data.filter(call => {
                        const transcript = call.transcript || call.text || '';
                        const matchesSearch = (call.caller || '').toLowerCase().includes(searchTerm) || 
                                             transcript.toLowerCase().includes(searchTerm);
                        const matchesDate = !dateFilter || (call.timestamp || '').startsWith(dateFilter);
                        return matchesSearch && matchesDate;
                    });
                    console.log('Filtered Data:', filtered); // Debug: Log filtered data
                    render(filtered);
                };

                searchInput.addEventListener('input', filterAndRender);
                filterDate.addEventListener('change', filterAndRender);
                filterAndRender();

                const clearBtn = document.getElementById('clearAll');
                if (clearBtn) {
                    clearBtn.addEventListener('click', () => {
                        if (confirm('Are you sure you want to clear all call data?')) {
                            fetch('clear_history.php', { method: 'POST' })
                                .then(response => {
                                    if (response.ok) {
                                        location.reload();
                                    } else {
                                        console.error('Failed to clear history:', response.statusText);
                                        alert('Failed to clear call history.');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error clearing history:', error);
                                    alert('An error occurred while clearing call history.');
                                });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading history:', error);
                alert('An error occurred while loading call history. Please try again.');
            });
    </script>
</body>
</html>