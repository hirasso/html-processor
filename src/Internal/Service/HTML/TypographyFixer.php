<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\HTML;

use Hirasso\HTMLProcessor\Internal\Service\Contract\HTMLServiceContract;
use JoliTypo\Fixer;
use JoliTypo\FixerInterface;

final class TypographyFixer implements HTMLServiceContract
{
    private string $locale;

    /** @var list<FixerInterface> $rules */
    private array $rules;

    public function prio(): int
    {
        return 0;
    }

    public function __construct(
        string $locale,
    ) {
        $this->locale = $this->parseLocale($locale);
    }

    /**
     * Parse the provided locale
     */
    private function parseLocale(string $locale): string
    {
        if (!$parts = \preg_split('/[_-]/', $locale, 2)) {
            throw new \Exception("Could not parse locale: '{$locale}'. Expected format: 'de', 'de_DE', or 'de_DE_formal'");
        }
        return \implode('_', $parts);
    }

    /** @param list<FixerInterface> $rules */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Get rules for the fixer. Fall back to defaults
     * for the current locale if available
     *
     * @return null|list<FixerInterface|string>
     */
    private function getRules(): ?array
    {
        $defaultRules = Fixer::RECOMMENDED_RULES_BY_LOCALE[$this->locale] ?? null;

        $rules = empty($this->rules)
            ? $defaultRules
            : $this->rules;

        return $rules;
    }

    /**
     * Run the registered TypographyFixer against the string of HTML
     */
    public function run(string $html): string
    {
        if (empty($rules = $this->getRules())) {
            return $html;
        }

        $fixer = new Fixer($rules);
        $fixer->setLocale($this->locale);

        return $fixer->fix($html);
    }
}
