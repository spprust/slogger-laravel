<?php

namespace SLoggerLaravel\RequestPreparer;

class RequestDataFormatters
{
    /** @var RequestDataFormatter[] */
    protected array $items = [];

    protected bool $sorted = false;

    public function add(RequestDataFormatter $formatter): static
    {
        $this->items[] = $formatter;

        $this->sorted = false;

        return $this;
    }

    /**
     * @return RequestDataFormatter[]
     */
    public function getItems(): array
    {
        if (!$this->sorted) {
            $this->items = collect($this->items)
                ->sortBy(
                    static function (RequestDataFormatter $item) {
                        return ($item->isHideAllRequestParameters() || $item->isHideAllResponseData()) ? 0 : 1;
                    }
                )
                ->all();

            $this->sorted = true;
        }

        return $this->items;
    }
}
