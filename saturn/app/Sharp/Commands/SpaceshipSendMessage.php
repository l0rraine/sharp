<?php

namespace App\Sharp\Commands;

use Code16\Sharp\EntityList\Commands\InstanceCommand;
use Code16\Sharp\Form\Fields\SharpFormCheckField;
use Code16\Sharp\Form\Fields\SharpFormTextareaField;

class SpaceshipSendMessage extends InstanceCommand
{

    /**
     * @return string
     */
    public function label(): string
    {
        return "Send a text message";
    }

    /**
     * @param string $instanceId
     * @param array $params
     * @return array
     */
    public function execute($instanceId, array $params = [])
    {
        $this->validate($params, [
            "message" => "required"
        ]);

        // TODO execute method

        return $this->refresh($instanceId);
    }

    function buildForm()
    {
        $this->addField(
            SharpFormTextareaField::make("message")
                ->setLabel("Message")

        )->addField(
            SharpFormCheckField::make("now", "Send right now?")
                ->setHelpMessage("Otherwise it will be sent next night.")
        );
    }
}