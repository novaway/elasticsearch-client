Feature: Search on index


    Background:
        Given there is no index named "my_index"
        And I create an index named "my_index" with the configuration from "data/config.yml"
        When I add objects of type "my_type" to index "my_index" with data :
            | id | first_name | nick_name    | age | gender | description                                                     |
            | 1  | Barry      | Flash        | 33  | male   | I'm the fastest man alive                                       |
            | 2  | Diana      | Wonder Woman | 910 | female | I'm badass, period                                              |
            | 3  | Bruce      | Batman       | 45  | male   | I'm rich and I fight crime, with my dead parents money          |
            | 4  | Barbara    | Batgirl      | 27  | female | I team up with Batman to fight crime                            |
            | 5  | Oliver     | Green Arrow  | 35  | male   | I'm rich and I fight crime, pretty much like Batman, with a bow |
            | 6  | Selena     | Catwoman     | 38  | female | I <3 cats ... and batman                                        |
            | 7  | Skwi       | Batman       | 33  | male   | I <3 pasta ... and batman                                        |

    Scenario: Search on one fields
        Given I build a query matching :
            | field       | value  | condition |
            | description | batman | should    |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[4;5;6;7]"

    Scenario: Search over several field
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[3;4;5;6;7]"

    Scenario: Combine SHOULD an MUST matches
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | must      |
            | description | batman | should    |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[3;7]"

    Scenario: Term filter
        Given I build a query with filter :
            | type | field  | value  |
            | term | gender | female |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[2;4;6]"

    Scenario: InArray filter
        Given I build a query with filter :
            | type     | field | value |
            | in_array | age   | 45;35 |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[3;5]"

    Scenario: Range filter
        Given I build a query with filter :
            | type  | field | value | operator |
            | range | age   | 40    | gte      |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[2;3]"

    Scenario: Combine matches and filters
        Given I build a query matching :
            | field       | value  | condition |
            | description | batman | should    |
        And I build the query with filter :
            | type  | field | value | operator |
            | range | age   | 30    | lt       |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[4]"

    Scenario: Offset and Limit results
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        And I set query offset to 1 and limit to 2
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain only ids "[3;6]"
        And  the result should contain 5 hits

    Scenario: Minimum score
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        And I set query minimum score to 0.1
        When I execute it on the index named "my_index" for type "my_type"
        Then the result should contain exactly ids "[7;3;6;4]"

    Scenario: Highlight after Search over several field
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        And I set highlight tags to "<strong>" and "</strong>" for description
        And I set highlight tags to "<em>" and "</em>" for nick_name
        When I execute it on the index named "my_index" for type "my_type"
        Then the result with the id 7 should contain "<em>Batman</em>" in "nick_name"
        And the result with the id 7 should contain "I <3 pasta ... and <strong>batman</strong>" in "description"
        And the result with the id 3 should contain "<em>Batman</em>" in "nick_name"

    Scenario: Aggregations
        Given I build a query with aggregation :
            | name          | category  | field     |
            | sum_age       | sum       | age       |
            | genders       | terms     | gender    |
        When I execute it on the index named "my_index" for type "my_type"
        Then the result for aggregation "sum_age" should contain 1121
        And the bucket result for aggregation "genders" should contain 4 result for "male"
        And the bucket result for aggregation "genders" should contain 3 result for "female"