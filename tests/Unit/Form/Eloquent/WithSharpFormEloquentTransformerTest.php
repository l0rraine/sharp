<?php

namespace Code16\Sharp\Tests\Unit\Form\Eloquent;

use Code16\Sharp\Form\Eloquent\WithSharpFormEloquentTransformer;
use Code16\Sharp\Form\Fields\SharpFormTextField;
use Code16\Sharp\Form\SharpForm;
use Code16\Sharp\Tests\Fixtures\Person;
use Code16\Sharp\Utils\Transformers\SharpAttributeTransformer;

class WithSharpFormEloquentTransformerTest extends SharpFormEloquentBaseTest
{

    /** @test */
    function we_can_retrieve_an_array_version_of_a_model()
    {
        $person = Person::create([
            "name" => "John Wayne"
        ]);

        $form = new WithSharpFormEloquentTransformerTestForm();

        $this->assertArraySubset([
                "name" => "John Wayne"
            ], $form->find($person->id)
        );
    }

    /** @test */
    function belongsTo_is_handled()
    {
        $mother = Person::create([
            "name" => "Jane Wayne"
        ]);

        $person = Person::create([
            "name" => "John Wayne",
            "mother_id" => $mother->id
        ]);

        $form = new WithSharpFormEloquentTransformerTestForm();

        $this->assertArraySubset([
                "mother_id" => $person->mother_id,
                "mother" => ["id" => $mother->id, "name" => $mother->name],
            ], $form->find($person->id)
        );
    }

    /** @test */
    function hasOne_is_handled()
    {
        $mother = Person::create([
            "name" => "Jane Wayne"
        ]);

        $son = Person::create([
            "name" => "John Wayne",
            "mother_id" => $mother->id
        ]);

        $form = new WithSharpFormEloquentTransformerTestForm();

        $this->assertArraySubset([
            "elder_son" => ["id" => $son->id, "name" => $son->name],
        ], $form->find($mother->id)
        );
    }

    /** @test */
    function hasMany_is_handled()
    {
        $mother = Person::create([
            "name" => "Jane Wayne"
        ]);

        $son1 = Person::create([
            "name" => "John Wayne",
            "mother_id" => $mother->id
        ]);

        $son2 = Person::create([
            "name" => "Bill Wayne",
            "mother_id" => $mother->id
        ]);

        $form = new WithSharpFormEloquentTransformerTestForm();

        $this->assertArraySubset([
                "sons" => [
                    ["id" => $son1->id, "name" => $son1->name],
                    ["id" => $son2->id, "name" => $son2->name],
                ]
            ], $form->find($mother->id)
        );
    }

    /** @test */
    function belongsToMany_is_handled()
    {
        $person1 = Person::create([
            "name" => "John Wayne"
        ]);

        $person2 = Person::create([
            "name" => "Louise Brooks"
        ]);

        $person3 = Person::create([
            "name" => "Claire Trevor"
        ]);

        $person1->friends()->attach([
            $person2->id, $person3->id
        ]);

        $form = new WithSharpFormEloquentTransformerTestForm();

        $this->assertArraySubset([
                "friends" => [
                    ["id" => $person2->id, "name" => $person2->name],
                    ["id" => $person3->id, "name" => $person3->name],
                ]
            ], $form->find($person1->id)
        );
    }

    /** @test */
    function morphOne_is_handled()
    {
        $person = Person::create(["name" => "John Wayne"]);
        $person->picture()->create(["file" => "test.jpg"]);

        $form = new WithSharpFormEloquentTransformerTestForm();

        $this->assertArraySubset([
                "picture" => ["file" => "test.jpg"],
            ], $form->find($person->id)
        );
    }

    /** @test */
    function morphMany_is_handled()
    {
        $person = Person::create(["name" => "John Wayne"]);
        $person->pictures()->create(["file" => "test.jpg"]);

        $form = new WithSharpFormEloquentTransformerTestForm();

        $this->assertArraySubset([
                "pictures" => [["file" => "test.jpg"]],
            ], $form->find($person->id)
        );
    }

    /** @test */
    function we_handle_the_relation_separator()
    {
        $mother = Person::create(["name" => "Jane Wayne"]);
        $son = Person::create(["name" => "AAA", "mother_id" => $mother->id]);
        $person = Person::create(["name" => "BBB"]);

        $form = new class extends WithSharpFormEloquentTransformerTestForm {
            function buildFormFields() {
                $this->addField(SharpFormTextField::make("mother:name"));
            }
        };

        $this->assertArraySubset([
                "mother:name" => "Jane Wayne"
            ], $form->find($son->id)
        );

        $this->assertArraySubset([
                "mother:name" => null
            ], $form->find($person->id)
        );
    }

    /** @test */
    function we_can_use_a_closure_as_a_custom_transformer()
    {
        $person = Person::create([
            "name" => "John Wayne"
        ]);

        $form = new WithSharpFormEloquentTransformerTestForm();
        $form->setCustomTransformer("name", function($person) {
            return strtoupper($person->name);
        });

        $this->assertArraySubset(
            ["name" => "JOHN WAYNE"],
            $form->find($person->id)
        );
    }

    /** @test */
    function we_can_use_a_class_as_a_custom_transformer()
    {
        $person = Person::create([
            "name" => "John Wayne"
        ]);

        $form = new WithSharpFormEloquentTransformerTestForm;
        $form->setCustomTransformer("name", SharpAttributeUppercaseTransformer::class);

        $this->assertArraySubset(
            ["name" => "JOHN WAYNE"],
            $form->find($person->id)
        );
    }
}

class WithSharpFormEloquentTransformerTestForm extends SharpForm
{
    use WithSharpFormEloquentTransformer;

    function find($id): array
    {
        return $this->transform(
            Person::whereId($id)
                ->with(["sons", "elderSon", "mother", "friends", "picture", "pictures"])
                ->firstOrFail()
        );
    }

    function update($id, array $data): bool { return false; }
    function delete($id): bool { return false; }
    function buildFormLayout() {}
    function buildFormFields() {}
}

class SharpAttributeUppercaseTransformer implements SharpAttributeTransformer
{
    function apply($instance, string $attribute)
    {
        return strtoupper($instance->$attribute);
    }
}