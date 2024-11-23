<?php

namespace SLoggerLaravel\RequestPreparer;

class SLoggerRequestDataFormatters
{
    /** @var SLoggerRequestDataFormatter[] */
    protected array $items = [];

    protected bool $sorted = false;

    public function add(SLoggerRequestDataFormatter $formatter): static
    {
        $this->items[] = $formatter;

        $this->sorted = false;

        return $this;
    }

    /**
     * @return SLoggerRequestDataFormatter[]
     */
    public function getItems(): array
    {
        if (!$this->sorted) {
            $this->items = collect($this->items)
                ->sortBy(
                    static function (SLoggerRequestDataFormatter $item) {
                        return ($item->isHideAllRequestParameters() || $item->isHideAllResponseData()) ? 0 : 1;
                    }
                )
                ->all();

            $this->sorted = true;
        }

        return $this->items;
    }
}
