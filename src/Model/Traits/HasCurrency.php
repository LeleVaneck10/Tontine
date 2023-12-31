<?php

namespace Siak\Tontine\Model\Traits;

use Siak\Tontine\Service\LocaleService;

use function app;
use function intval;

trait HasCurrency
{
    /**
     * Format an attribute value
     *
     * @param string $attr
     * @param bool $hideSymbol
     *
     * @return string
     */
    public function money(string $attr, bool $hideSymbol = false): string
    {
        return app(LocaleService::class)->formatMoney(intval($this->$attr), $hideSymbol);
    }

    /**
     * Get the amount to display
     *
     * @return float
     */
    public function getAmountValueAttribute(): float
    {
        return app(LocaleService::class)->getMoneyValue(intval($this->amount));
    }
}
