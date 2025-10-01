<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edge TTS Vocal Synthesis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .container { max-width: 900px; margin-top: 50px; }
        textarea { min-height: 160px; }
        .audio-player { margin-top: 20px; }
        .blue-gradient-bg {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white; 
            padding: 10px; 
            border-radius: .5rem; 
            display: inline-block;
        }
        .blue-shadow { 
            text-shadow: none;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">
            <span class="blue-gradient-bg blue-shadow">Edge TTS demo</span>
        </h1>
        <div class="card p-4 shadow-sm">
            <div class="mb-4">
                <label for="textToSpeak" class="form-label">Content to convert:</label>
                <div class="btn-group mb-2" role="group">
                    <button type="button" class="btn btn-primary" id="modeTextBtn">
                        <i class="bi bi-text-paragraph me-1"></i> Text
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="modeSsmlBtn">
                        <i class="bi bi-code-slash me-1"></i> SSML
                    </button>
                </div>
                
                <textarea class="form-control" id="textToSpeak" rows="6" placeholder="Enter text here..."></textarea>
                <small id="ssmlHint" class="form-text text-muted" style="display: none;">
                    Content must start with &lt;speak&gt; and use SSML
                </small>
            </div>
    
            <div id="simpleControls" class="row mb-4">
                <div class="col-md-6">
                    <label for="voiceSelect" class="form-label">Voice :</label>
                    <select class="form-select" id="voiceSelect">
                        @php $currentLocale = null; @endphp
                        @foreach ($voices as $voice)
                            @php
                                $locale = substr($voice['ShortName'], 0, 5); // ex: 'en-GB'
                            @endphp
                            @if ($locale !== $currentLocale)
                                @if ($currentLocale !== null)
                                    </optgroup>
                                @endif
                                <optgroup label="{{ $voice['Locale'] }}">
                                @php $currentLocale = $locale; @endphp
                            @endif
                            <option value="{{ $voice['ShortName'] }}" @if($voice['ShortName'] === 'en-GB-RyanNeural') selected @endif>
                                {{ $voice['LocalName'] }} ({{ $voice['Gender'] }}) - {{ $voice['ShortName'] }}
                            </option>
                        @endforeach
                        @if ($currentLocale !== null)
                            </optgroup>
                        @endif
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="rateSelect" class="form-label">Rate:</label>
                    <select class="form-select" id="rateSelect">
                        <option value="-50%">Very slow</option>
                        <option value="-25%">Slow</option>
                        <option value="0%" selected>Casual</option>
                        <option value="25%">Fast</option>
                        <option value="50%">Very fast</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="volumeSelect" class="form-label">Volume:</label>
                    <select class="form-select" id="volumeSelect">
                        <option value="-50%">Low</option>
                        <option value="-25%">Middle low</option>
                        <option value="0%" selected>Casual</option>
                        <option value="25%">Middle high</option>
                        <option value="50%">High</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="pitchSelect" class="form-label">Pitch:</label>
                    <select class="form-select" id="pitchSelect">
                        <option value="-50Hz">Very deep</option>
                        <option value="-20Hz">Deep</option>
                        <option value="0Hz" selected>Casual</option>
                        <option value="20Hz">high-pitched</option>
                        <option value="50Hz">Very high-pitched</option>
                    </select>
                </div>
            </div>
    
            <div class="d-grid gap-2">
                <button id="speakButton" class="btn btn-primary btn-lg">
                    <i class="bi bi-play-fill me-2"></i> Listen to the text
                </button>
            </div>
    
            <div class="audio-player text-center">
                <audio id="audioPlayer" controls></audio>
            </div>
        </div>
        
        @if (empty($voices))
            <div class="alert alert-warning mt-3">
                ⚠️ **Warning :** Unable to retrieve the complete list of voices. Check the server connection to the Edge TTS service.
            </div>
        @endif
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const speakButton = document.getElementById('speakButton');
            const textToSpeak = document.getElementById('textToSpeak');
            const voiceSelect = document.getElementById('voiceSelect');
            const rateSelect = document.getElementById('rateSelect');
            const volumeSelect = document.getElementById('volumeSelect');
            const pitchSelect = document.getElementById('pitchSelect');
            const audioPlayer = document.getElementById('audioPlayer');
            const modeTextBtn = document.getElementById('modeTextBtn');
            const modeSsmlBtn = document.getElementById('modeSsmlBtn');
            const ssmlHint = document.getElementById('ssmlHint');
            const simpleControls = document.getElementById('simpleControls');
    
            let isSsmlMode = false;
        
            const defaultText = "Born in the garret, in the kitchen bred,\n" +
                                "Promoted thence to deck her mistress' head\n" +
                                "Next for some gracious service unexpress'd";
            const defaultSsml = `<speak version="1.0"
       xmlns="http://www.w3.org/2001/10/synthesis"
       xmlns:mstts="https://www.w3.org/2001/mstts"
       xml:lang="es-CO">
  <voice name="es-CO-GonzaloNeural">
    <mstts:express-as style="narration-professional">
      <prosody rate="+5%" pitch="+10Hz" volume="+0%">
        Hola, este es un ejemplo de <emphasis>SSML</emphasis>.
        <break time="400ms" />
        El número es <say-as interpret-as="cardinal">2025</say-as>.
        La palabra se pronuncia
        <phoneme alphabet="ipa" ph="ˈxola">hola</phoneme>.
      </prosody>
    </mstts:express-as>
  </voice>
</speak>`;
    
            // Text init
            textToSpeak.value = defaultText;
   
            function setMode(isSsml) {
                isSsmlMode = isSsml;
                if (isSsml) {
                    // SSML mode
                    modeTextBtn.classList.remove('btn-primary');
                    modeTextBtn.classList.add('btn-outline-primary');
                    modeSsmlBtn.classList.add('btn-primary');
                    modeSsmlBtn.classList.remove('btn-outline-primary');
                    
                    simpleControls.style.display = 'none';
                    ssmlHint.style.display = 'block';
                    textToSpeak.value = defaultSsml;
                } else {
                    // Text mode
                    modeSsmlBtn.classList.remove('btn-primary');
                    modeSsmlBtn.classList.add('btn-outline-primary');
                    modeTextBtn.classList.add('btn-primary');
                    modeTextBtn.classList.remove('btn-outline-primary');
                    
                    simpleControls.style.display = 'flex'; 
                    ssmlHint.style.display = 'none';
                    textToSpeak.value = defaultText;
                }
            }
            
            // Initialization of the correct display mode upon loading
            setMode(false); 
    
            modeTextBtn.addEventListener('click', () => setMode(false));
            modeSsmlBtn.addEventListener('click', () => setMode(true));
    
            speakButton.addEventListener('click', function () {
                const text = textToSpeak.value.trim();
                const voice = voiceSelect.value;
                
                if (text === '') {
                    alert('Please enter text to be read.');
                    return;
                }
    
                // --- SSML Logic ---
                let rate = '0%', volume = '0%', pitch = '0Hz';
                
                // If you are in PLAIN TEXT mode, use the values of the selectors.
                if (!isSsmlMode) {
                    rate = rateSelect.value;
                    volume = volumeSelect.value;
                    pitch = pitchSelect.value;
                } 
                // If you are in SSML mode, the values remain at ‘0%’/'0Hz' (the TTS service will ignore these parameters because the text is in SSML)..
                // ----------------------------------------
                
                speakButton.disabled = true;
                speakButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading...';
    
                // Construction of the streaming URL with the variables ‘rate’, ‘volume’, ‘pitch’ ABOVE.
                const params = new URLSearchParams({
                    text: text,
                    voice: voice,
                    rate: rate,
                    volume: volume,
                    pitch: pitch 
                }).toString();
                
                const streamUrl = `{{ route('edge-tts.stream') }}?${params}`;
                
                audioPlayer.src = streamUrl;
                audioPlayer.load();
                audioPlayer.play()
                    .catch(error => {
                        console.error("Audio playback error:", error);
                        alert("Unable to play audio. Check the console for details.");
                        speakButton.textContent = 'Listen to the text';
                        speakButton.disabled = false;
                    });
            });
    
            // Reactivation of the button after playback
            audioPlayer.addEventListener('ended', function() {
                speakButton.innerHTML = '<i class="bi bi-play-fill me-2"></i> Listen to the text';
                speakButton.disabled = false;
            });
            audioPlayer.addEventListener('error', function() {
                speakButton.innerHTML = '<i class="bi bi-play-fill me-2"></i> Listen to the text';
                speakButton.disabled = false;
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>