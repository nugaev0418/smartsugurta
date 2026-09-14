<?php

namespace backend\component\bot;

/**
 * Pages:: constant -> stage handler. Used solely for actionStart()'s final
 * dispatch (the old inner switch($this->page)), which only ever called
 * handle*Page() methods. Transitions between stages (calling another
 * stage's show*() method) go through direct constructor-injected
 * references between handler classes instead of back through here — see
 * each *StageHandler's constructor.
 */
class BotStageRegistry
{
    /** @var array<string, BotStageInterface> */
    private array $handlers = [];

    public function register(string $page, BotStageInterface $handler): void
    {
        $this->handlers[$page] = $handler;
    }

    public function get(string $page): ?BotStageInterface
    {
        return $this->handlers[$page] ?? null;
    }
}
