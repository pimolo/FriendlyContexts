<?php

namespace Knp\FriendlyContexts\Context;

use Behat\MinkExtension\Context\MinkContext as BaseMinkContext;
use Knp\FriendlyContexts\Utils\Asserter;
use Knp\FriendlyContexts\Utils\TextFormater;

class MinkContext extends BaseMinkContext
{
    /**
     * @When /^(?:|I )(follow|press) the "(?P<name>[^"]*)" (?P<element>[^"]*)$/
     * @When /^(?:|I )(follow|press) the first "(?P<name>[^"]*)" (?P<element>[^"]*)$/
     * @When /^(?:|I )(follow|press) the (?P<nbr>\d*)(st|nd|rd|th) "(?P<name>[^"]*)" (?P<element>[^"]*)$/
     **/
    public function clickElement($name, $element, $nbr = 1, $filterCallback = null)
    {
        $this->elementAction($name, $element, $nbr, function ($e) { $e->click(); }, $filterCallback);
    }

    /**
     * @When /^(?:|I )(?P<state>check|uncheck) the "(?P<name>[^"]*)" (?P<element>radio|checkbox)$/
     * @When /^(?:|I )(?P<state>check|uncheck) the first "(?P<name>[^"]*)" (?P<element>radio|checkbox)$/
     * @When /^(?:|I )(?P<state>check|uncheck) the (?P<nbr>\d*)(st|nd|rd|th) "(?P<name>[^"]*)" (?P<element>radio|checkbox)$/
     **/
    public function checkElement($state, $name, $element, $nbr = 1)
    {
        $this->elementAction(
            $name,
            'field',
            $nbr,
            function ($e) use ($state)   { if ('check' === $state) { $e->check(); } else { $e->uncheck(); } },
            function ($e) use ($element) { return $element === $e->getAttribute('type'); }
        );
    }

    /**
     * @Then /^(?:|I )should(?P<should>| not) see (?P<nbr>\d*) "(?P<name>[^"]*)" (?P<element>link|button|radio|checkbox)$/
     **/
    public function nbrElement($should, $nbr, $name, $element)
    {
        $type = in_array($element, [ 'checkbox', 'radio' ]) ? 'field' : $element;
        $filterCallback = null;

        if ('field' === $type) {
            $filterCallback = function ($e) use ($element) { return $element === $e->getAttribute('type'); };
        }

        $elements = $this->searchElement($name, $type, $filterCallback);

        $message = sprintf('%s %s found', $nbr, $element);

        if (' not' === $should) {
            $this->getAsserter()->assertEquals($nbr, count($elements), $message);
        } else {
            $this->getAsserter()->assertNotEquals($nbr, count($elements), $message);
        }
    }

    /**
     * @Then /^(?:|I )should(?P<should>| not) see a "(?P<name>[^"]*)" (?P<element>link|button|radio|checkbox)$/
     **/
    public function seeElement($should, $name, $element)
    {
        $type = in_array($element, [ 'checkbox', 'radio' ]) ? 'field' : $element;
        $filterCallback = null;

        if ('field' === $type) {
            $filterCallback = function ($e) use ($element) { return $element === $e->getAttribute('type'); };
        }

        $elements = $this->searchElement($name, $type, $filterCallback);

        $message = sprintf('%s %s%s found', $name, $element, ' not' === $should ? '' : ' not');

        if (' not' === $should) {
            $this->getAsserter()->assert(0 == count($elements), $message);
        } else {
            $this->getAsserter()->assert(0 < count($elements), $message);
        }
    }

    /**
     * @When /^(?:|I )(follow|press) the last "(?P<name>[^"]*)" (?P<element>[^"]*)$/
     **/
    public function clicklastElement($name, $element)
    {
        $this->clickElement($link, $element, -1);
    }

    /**
     * @When /^(?:|I )follow the link containing "(?P<link>(?:[^"]|\\")*)"$/
     */
    public function clickLinkContaining($link)
    {
        parent::clickLink($link);
    }

    public function clickLink($link)
    {
        $this->clickElement($link, 'link', 1, function ($e) use ($link) { return $link === $e->getText(); });
    }

    protected function searchElement($locator, $element, $filterCallback = null)
    {
        $page  = $this->getSession()->getPage();
        $locator = $this->fixStepArgument($locator);

        $elements = $page->findAll('named', array(
            $element, $this->getSession()->getSelectorsHandler()->xpathLiteral($locator)
        ));

        if (null !== $filterCallback && is_callable($filterCallback)) {
            $elements = array_values(array_filter($elements, $filterCallback));
        }

        return $elements;
    }

    protected function elementAction($locator, $element, $nbr = 1, $actionCallback, $filterCallback = null)
    {
        $elements = $this->searchElement($locator, $element, $filterCallback);

        $nbr = is_numeric($nbr) ? intval($nbr) : $nbr;
        $nbr = is_string($nbr) ? 1 : (-1 === $nbr ? count($elements) : $nbr);

        $this
            ->getAsserter()
            ->assert(
                $nbr <= count($elements),
                sprintf('Expected to find almost %s "%s" %s, %s found', $nbr, $locator, $element, count($elements))
            )
        ;

        $e = $elements[$nbr - 1];

        $actionCallback($e);
    }

    protected function getAsserter()
    {
        return new Asserter(new TextFormater);
    }
}