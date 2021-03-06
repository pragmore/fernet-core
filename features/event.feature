Feature: Event
  In order to write less code
  As a developer
  I want to link directly one event to a component method

  Scenario: Creating a simple static component
    Given the component defined in the class
    """
    class SimpleEvent
    {
      private bool $clicked = false;

      public function handleClick()
      {
        $this->clicked = true;
      }

      public function __toString(): string
      {
        $text = $this->clicked ? 'Yes' : 'No';
        return "<p>$text</p><a @onClick='handleClick'>Toggle</a>";
      }
    }
    """
    Given the component defined in the class
    """
    class SimpleEventMain
    {
      public function __toString(): string
      {
        return "<html><body><SimpleEvent /></body></html>";
      }
    }
    """
    When the main component is "SimpleEventMain" and we navigate to "/"
    And the link "Toggle" is clicked
    Then I can see the text "Yes" on "p"
