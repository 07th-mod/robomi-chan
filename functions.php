<?php

function loadFile($filename)
{
    $file = fopen($filename, 'r');
    $i = 0;
    while (!feof($file)) {
        ++$i;
        $line = fgets($file);
        if ($line) {
            saveLine($i, $line, pathinfo($filename, PATHINFO_FILENAME));
        }
    }
    fclose($file);
}

function saveLine($lineNumber, $line, $file)
{
    $parts = explode("\t", $line, 2);

    if (count($parts) < 2) {
        throw new \Exception(sprintf('No tab found "%s:%s".', $file, $lineNumber));
    }

    list($voice, $text) = $parts;

    dibi::query('INSERT INTO [voices]', [
        'text' => \Nette\Utils\Strings::trim($text),
        'voice' => \Nette\Utils\Strings::trim($voice),
        'file' => $file,
        'line' => $lineNumber,
    ]);
}

function processFile($filename)
{
    $matcher = new VoiceMatcher();
    $file = fopen($filename, 'r');
    @unlink($filename . '.new');
    $output = fopen($filename . '.new', 'w');
    $inserted = 0;
    $i = 0;
    $lastVoice = null;
    while (!feof($file)) {
        ++$i;
        $line = fgets($file);
        if (strpos($line, 'OutputLine(') !== false) {
            if ($match = \Nette\Utils\Strings::match($line, '~^\\s++OutputLine\\(NULL,\\s++"([^"]++)"~')) {
                $voice = $matcher->findVoice(\Nette\Utils\Strings::trim($match[1]));
                if ($voice && $lastVoice !== $voice) {
                    ++$inserted;
                    fwrite($output, "\tPlaySE(4, \"$voice\", 128, 64);\n");
                    $lastVoice = $voice;
                }
            }
        }
        fwrite($output, $line);
    }
    fclose($file);
    fclose($output);
    unlink($filename);
    rename($filename . '.new', $filename);
    
    echo "Inserted $inserted voice lines to " . pathinfo($filename, PATHINFO_BASENAME) . ".\n";
}

class VoiceMatcher
{
    private $lastFile = null;

    public function findVoice($text)
    {
        $text = strtr($text, [
            '〜' => '～',
        ]);

        if ($match = $this->searchNormal($text)) {
            return $match;
        }

        if ($match = $this->removeDot($text)) {
            return $match;
        }

        if ($match = $this->removeDotBeforeQuote($text)) {
            return $match;
        }

        if ($match = $this->searchStart($text)) {
            return $match;
        }

        if ($match = $this->searchLevenshtein($text)) {
            return $match;
        }
    }

    private function searchNormal($text)
    {
        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] = %s', $text)->fetchAll();
        if (count($rows) === 1) {
            $this->lastFile = $rows[0]['file'];
            return $rows[0]['voice'];
        }

        if (!$this->lastFile) {
            return;
        }

        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] = %s AND file = %s', $text, $this->lastFile)->fetchAll();
        if (count($rows) === 1) {
            return $rows[0]['voice'];
        }
    }

    private function searchStart($text)
    {
        if (\Nette\Utils\Strings::length($text) < 5) {
            return;
        }

        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] LIKE %s OR [text] LIKE %s', $text . '%', '「' . $text . '%')->fetchAll();
        if (count($rows) === 1) {
            $this->lastFile = $rows[0]['file'];
            return $rows[0]['voice'];
        }

        if (!$this->lastFile) {
            return;
        }

        $rows = dibi::query('SELECT * FROM [voices] WHERE ([text] LIKE %s OR [text] LIKE %s) AND file = %s', '「' . $text . '%', $text . '%', $this->lastFile)->fetchAll();
        if (count($rows) === 1) {
            return $rows[0]['voice'];
        }
    }

    private function searchLevenshtein($text)
    {
        $cut = round(\Nette\Utils\Strings::length($text) / 5);
        if ($cut <= 1) {
            return;
        }
        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] LIKE %s AND levenshtein_ratio([text], %s) >= 90', '%' . \Nette\Utils\Strings::subString($text, $cut, -$cut) . '%', $text)->fetchAll();
        if (count($rows) === 1) {
            $this->lastFile = $rows[0]['file'];
            return $rows[0]['voice'];
        }

        if (!$this->lastFile) {
            return;
        }

        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] LIKE %s AND levenshtein_ratio([text], %s) >= 90 AND file = %s', '%' . \Nette\Utils\Strings::subString($text, $cut, -$cut) . '%', $text, $this->lastFile)->fetchAll();
        if (count($rows) === 1) {
            return $rows[0]['voice'];
        }
    }

    private function removeDot($text)
    {
        if (!\Nette\Utils\Strings::endsWith($text, '。')) {
            return;
        }

        $text = \Nette\Utils\Strings::subString($text, 0, - \Nette\Utils\Strings::length('。'));

        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] = %s', $text)->fetchAll();
        if (count($rows) === 1) {
            $this->lastFile = $rows[0]['file'];
            return $rows[0]['voice'];
        }

        if (!$this->lastFile) {
            return;
        }

        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] = %s AND file = %s', $text, $this->lastFile)->fetchAll();
        if (count($rows) === 1) {
            return $rows[0]['voice'];
        }
    }

    private function removeDotBeforeQuote($text)
    {
        if (!\Nette\Utils\Strings::endsWith($text, '。」')) {
            return;
        }

        $text = \Nette\Utils\Strings::subString($text, 0, - \Nette\Utils\Strings::length('。」')) . '」';

        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] = %s', $text)->fetchAll();
        if (count($rows) === 1) {
            $this->lastFile = $rows[0]['file'];
            return $rows[0]['voice'];
        }

        if (!$this->lastFile) {
            return;
        }

        $rows = dibi::query('SELECT * FROM [voices] WHERE [text] = %s AND file = %s', $text, $this->lastFile)->fetchAll();
        if (count($rows) === 1) {
            return $rows[0]['voice'];
        }
    }
}
