<?php

namespace App\Services\Rag;

class DistressDetector
{
    /**
     * Regex patterns for personal distress and self-harm keywords across EN, TA, SI,
     * specifically focused on first-person or direct-intent expressions to prevent
     * false positives on academic questions (e.g. literature, history, biology).
     */
    protected array $patterns = [
        // English: Requires first-person subject or direct intent (kill myself, end my life, want to die)
        '/(?:^|\b)(?:i[\s\S]{0,40}?(?:kill\s+myself|end\s+my\s+(?:own\s+)?life|take\s+my\s+(?:own\s+)?life|commit\s+suicide|cut\s+myself|harm\s+myself|hurt\s+myself|die\b)|(?:kill|harm|hurt|cut)\s+myself|(?:want|going)\s+to\s+(?:die|kill\s+myself|end\s+my\s+(?:own\s+)?life))\b/i',

        // Tamil script: First person (நான், எனக்கு, என்) with self-harm / suicide / dying intent
        '/(?:நான்|எனக்கு|என்|என்னை)[\s\S]{0,35}(?:சாக(?:ப்\s*போகிறேன்|ப்\s*போறேன்|\s*வேண்டும்|\s*தோணுது|\s*நினைக்கிறேன்)|\s*தற்கொலை\s*(?:செய்ய|செய்துக்க|பண்ண|செய்து\s*கொள்ள)|உயிரை\s*மாய்க்க)|(?:சாகப்\s*போகிறேன்|சாகப்\s*போறேன்|சாக\s*வேண்டும்\s*போல|தற்கொலை\s*செய்து\s*கொள்ளப்\s*போகிறேன்)/u',

        // Romanized Tamil (Tanglish): First person (naan, enaku) with dying / suicide intent
        '/(?:naan|enaku|ennai)[\s\S]{0,35}(?:saaga\s*(?:poren|vendum|thonuthu|poga\s*poren)|suicide\s*(?:panna|pannikiren|panren)|kill\s*myself)|(?:saaga\s*poren|saaga\s*vendum\s*pola)/i',

        // Sinhala script: First person (මම, මට, මගේ) with dying / suicide intent
        '/(?:මම|මට|මගේ)[\s\S]{0,35}(?:මැරෙන්න\s*(?:ඕන|හිතෙනවා|යනවා)|සියදිවි\s*නසා\s*(?:ගන්න|ගන්නවා|යනවා)|ජීවිතය\s*නැති\s*කරගන්න)|(?:මැරෙන්න\s*ඕන|මැරෙන්න\s*හිතෙනවා|සියදිවි\s*නසාගන්න\s*යනවා)/u',

        // Romanized Sinhala (Singlish): First person (mama, mata) with dying / suicide intent
        '/(?:mama|mata|mage)[\s\S]{0,35}(?:marenna\s*(?:ona|hithenawa|yanawa)|suicide\s*(?:karaganna|venna)|siyadiwi\s*nasa)|(?:marenna\s*ona|marenna\s*hithenawa)/i',
    ];

    /**
     * Check if the student's message indicates personal distress or self-harm.
     */
    public function isDistress(string $text): bool
    {
        $clean = trim($text);
        if (empty($clean)) {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a caring, supportive localized message encouraging help and listing helplines.
     *
     * @param string $language 'en', 'ta', or 'si'
     * @param string|null $instituteContact Optional institute phone or teacher contact
     */
    public function getCaringMessage(string $language, ?string $instituteContact = null): string
    {
        $configuredHelplines = config('rag.safety.helplines', []);

        $contactList = [];
        foreach ($configuredHelplines as $line) {
            $name = $line['name'] ?? 'Support';
            $number = !empty($line['number']) ? $line['number'] : null;
            $hours = !empty($line['hours']) ? " ({$line['hours']})" : '';

            // If this is the institute support slot and an institute contact was passed, use it
            if (empty($number) && str_contains(strtolower($name), 'institute') && !empty($instituteContact)) {
                $number = $instituteContact;
            }

            if (!empty($number)) {
                $contactList[] = "• {$name}: {$number}{$hours}";
            }
        }

        $helplineSectionTa = !empty($contactList) ? "\n\nஉதவி மற்றும் ஆதரவு அவசர எண்கள்:\n" . implode("\n", $contactList) : '';
        $helplineSectionSi = !empty($contactList) ? "\n\nසහාය සහ හදිසි උපකාරක දුරකථන අංක:\n" . implode("\n", $contactList) : '';
        $helplineSectionEn = !empty($contactList) ? "\n\nSupport & Crisis Helplines:\n" . implode("\n", $contactList) : '';

        return match ($language) {
            'ta' => "நீங்கள் இவ்வாறு உணர்வது மிகுந்த வருத்தமளிக்கிறது, ஆனால் நீங்கள் தனிமையில் இல்லை என்பதை தயவுசெய்து நினைவில் கொள்ளுங்கள். உடனடியாக உங்கள் ஆசிரியர் அல்லது நீங்கள் நம்பும் ஒரு பெரியவரிடம் பேசுங்கள். உங்களுக்கு உதவ எப்போதும் ஆதரவு தயாராக இருக்கிறது." . $helplineSectionTa,

            'si' => "ඔබ මේ ආකාරයෙන් හැඟෙන බව දැනගැනීම කණගාටුවට කරුණකි, නමුත් ඔබ තනිවී නැති බව කරුණාකර මතක තබා ගන්න. කරුණාකර ඔබ විශ්වාස කරන වැඩිහිටියෙකු හෝ ඔබගේ ගුරුවරයා සමඟ වහාම කතා කරන්න. ඔබට උපකාර කිරීමට සෑම විටම සහය ඇත." . $helplineSectionSi,

            default => "I am really sorry that you are feeling this way, but please know that you are not alone. Please reach out and talk to a trusted adult or your teacher right now. Support is always available to help you through this." . $helplineSectionEn,
        };
    }
}
