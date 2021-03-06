<?php

namespace Laravel\Dusk\Concerns;

use Closure;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Facebook\WebDriver\Exception\TimeOutException;

trait WaitsForElements
{
    /**
     * Execute the given callback in a scoped browser once the selector is available.
     *
     * @param  string  $selector
     * @param  Closure  $callback
     * @param  int  $seconds
     * @return $this
     */
    public function whenAvailable($selector, Closure $callback, $seconds = 5)
    {
        return $this->waitFor($selector, $seconds)->with($selector, $callback);
    }

    /**
     * Wait for the given selector to be visible.
     *
     * @param  string  $selector
     * @param  int  $seconds
     * @return $this
     */
    public function waitFor($selector, $seconds = 5)
    {
        return $this->waitUsing($seconds, 100, function () use ($selector) {
            return $this->resolver->findOrFail($selector)->isDisplayed();
        });
    }

    /**
     * Wait for the given selector to be not visible.
     *
     * @param  string  $selector
     * @param  int  $seconds
     * @return $this
     */
    public function waitUntilMissing($selector, $seconds = 5)
    {
        return $this->waitUsing($seconds, 100, function () use ($selector) {
            return ! $this->resolver->findOrFail($selector)->isDisplayed();
        });
    }

    /**
     * Wait for the given text to be visible.
     *
     * @param  string  $text
     * @param  int  $seconds
     * @return $this
     */
    public function waitForText($text, $seconds = 5)
    {
        return $this->waitUsing($seconds, 100, function () use ($text) {
            return Str::contains($this->resolver->findOrFail('')->getText(), $text);
        });
    }

    /**
     * Wait for the given link to be visible.
     *
     * @param  string  $link
     * @param  int  $seconds
     * @return $this
     */
    public function waitForLink($link, $seconds = 5)
    {
        return $this->waitUsing($seconds, 100, function () use ($link) {
            return $this->seeLink($link);
        });
    }

    /**
     * Wait until the given script returns true.
     *
     * @param  string  $script
     * @param  int  $seconds
     * @return $this
     */
    public function waitUntil($script, $seconds = 5)
    {
        if (! Str::startsWith($script, 'return ')) {
            $script = 'return '.$script;
        }

        if (! Str::endsWith($script, ';')) {
            $script = $script.';';
        }

        return $this->waitUsing($seconds, 100, function () use ($script) {
            return $this->driver->executeScript($script);
        });
    }

    /**
     * Wait for the given callback to be true.
     *
     * @param  int  $seconds
     * @param  int  $interval
     * @param  Closure  $callback
     * @param  string|null  $message
     * @return $this
     * @throws TimeOutException
     */
    public function waitUsing($seconds, $interval, Closure $callback, $message = null)
    {
        $this->pause($interval);

        $started = Carbon::now();

        while (true) {
            try {
                if ($callback()) {
                    break;
                }
            } catch (Exception $e) {
                //
            }

            if ($started->lt(Carbon::now()->subSeconds($seconds))) {
                throw new TimeOutException($message ?: "Waited {$seconds} seconds for callback.");
            }

            $this->pause($interval);
        }

        return $this;
    }
}
