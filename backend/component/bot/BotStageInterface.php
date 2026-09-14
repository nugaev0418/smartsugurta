<?php

namespace backend\component\bot;

/**
 * Contract for one FSM stage (or a small group of closely related stages —
 * see VehicleLookupStageHandler, PoliceSeasonStageHandler etc., which each
 * cover a few Pages:: constants and dispatch internally on $ctx->page).
 */
interface BotStageInterface
{
    /**
     * Renders the stage's screen and records it as the current page.
     * $params carries stage-specific hints — which of a multi-page
     * handler's sub-screens to show, or (MainMenuStageHandler) a custom
     * message to show instead of the default greeting.
     */
    public function show(BotContext $ctx, array $params = []): void;

    /**
     * Processes the incoming update while $ctx->page is (one of) this
     * stage's page(s). A stage with nothing to process for its page (e.g.
     * Pages::MAIN, which the original inner switch never matched) may
     * implement this as a no-op.
     */
    public function handle(BotContext $ctx): void;
}
