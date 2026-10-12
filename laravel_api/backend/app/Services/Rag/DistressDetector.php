<?php

namespace App\Services\Rag;

class DistressDetector
{
    /**
     * Regex patterns for personal distress and self-harm keywords across EN, TA, SI.
     */
    protected array $patterns = [
        // English
        '/\b(suicid|kill\s+myself|want\s+to\s+die|end\s+my\s+life|self[- ]harm|cut\s+myself|hurting\s+myself)\b/i',
        // Tamil
        '/(தற்கொலை|சாக\s*வேண்டும்|சாக\s*போறேன்|உயிரை\s*மாய்த்து)/u',
        // Sinhala
        '/(සියදිවි\s*නසා|මැරෙන්න\s*ඕන|ජීවිතය\s*නැති\s*කර)/u',
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
     * Get a caring, supportive localized message encouraging help.
     */
    public function getCaringMessage(string $language): string
    {
        return match ($language) {
            'ta' => "நீங்கள் இவ்வாறு உணர்வது மிகுந்த வருத்தமளிக்கிறது, ஆனால் நீங்கள் தனிமையில் இல்லை என்பதை நினைவில் கொள்ளுங்கள். தயவுசெய்து உங்கள் ஆசிரியர், பெற்றோர் அல்லது நீங்கள் நம்பும் ஒரு பெரியவரிடம் உடனடியாகப் பேசுங்கள். உங்களுக்கு உதவ அவர்கள் எப்போதும் தயாராக இருக்கிறார்கள்.",
            'si' => "ඔබ මේ ආකාරයෙන් හැඟෙන බව දැනගැනීම කණගාටුවට කරුණකි, නමුත් ඔබ තනිවී නැති බව කරුණාකර මතක තබා ගන්න. කරුණාකර ඔබගේ ගුරුවරයා, දෙමාපියන් හෝ ඔබ විශ්වාස කරන වැඩිහිටියෙකු සමඟ වහාම කතා කරන්න.",
            default => "I am really sorry that you are feeling this way, but please know that you are not alone. Please reach out to someone who can help right now—such as your teacher, parent, or a trusted adult. Help and support are always available.",
        };
    }
}
