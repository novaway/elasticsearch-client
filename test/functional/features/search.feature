Feature: Search on index

    Background:
        Given there is no index named "my_index"
        And I create an index named "my_index" with the configuration from "data/config_my_index.yml"
        And there is no index named "nested_index"
        And I create an index named "nested_index" with the configuration from "data/config_nested_index.yml"
        And there is no index named "my_geoindex"
        And I create an index named "my_geoindex" with the configuration from "data/config_my_geoindex.yml"
        When I add objects of type "_doc" to index "my_index" with data :
            | id | first_name | nick_name    | age | gender | description                                                     | license   |
            | 1  | Barry      | Flash        | 33  | male   | I'm the fastest man alive                                       | DC Comics |
            | 2  | Diana      | Wonder Woman | 910 | female | I'm badass, period                                              | DC Comics |
            | 3  | Bruce      | Batman       | 45  | male   | I'm rich and I fight crime, with my dead parents money          | DC Comics |
            | 4  | Barbara    | Batgirl      | 27  | female | I team up with Batman to fight crime                            | DC Comics |
            | 5  | Oliver     | Green Arrow  | 35  | male   | I'm rich and I fight crime, pretty much like Batman, with a bow | DC Comics |
            | 6  | Selena     | Catwoman     | 38  | female | I <3 cats ... and batman                                        | DC Comics |
            | 7  | Skwi       | Batman       | 33  | male   | I <3 pasta ... and batman                                       | null      |

    Scenario: Search on one fields
        Given I build a query matching :
            | field       | value  | condition |
            | description | batman | should    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[4;5;6;7]"

    Scenario: Search over several field
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[3;4;5;6;7]"

    Scenario: Combine SHOULD an MUST matches
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | must      |
            | description | batman | should    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[3;7]"

    Scenario: Term filter
        Given I build a query with filter :
            | type | field  | value  |
            | term | gender | female |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[2;4;6]"

    Scenario: InArray filter
        Given I build a query with filter :
            | type     | field | value |
            | in_array | age   | 45;35 |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[3;5]"

    Scenario: Range filter
        Given I build a query with filter :
            | type  | field | value | operator |
            | range | age   | 40    | gte      |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[2;3]"

    Scenario: Combine matches and filters
        Given I build a query matching :
            | field       | value  | condition |
            | description | batman | should    |
        And I build the query with filter :
            | type  | field | value | operator |
            | range | age   | 30    | lt       |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[4]"

    Scenario: Offset and Limit results
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        And I set query offset to 1 and limit to 2
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain only ids "[3;6]"
        And  the result should contain 5 hits

    Scenario: Minimum score
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        And I set query minimum score to 0.6
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[3;6;7]"

    Scenario: Highlight after Search over several field
        Given I build a query matching :
            | field       | value  | condition |
            | nick_name   | batman | should    |
            | description | batman | should    |
        And I set highlight tags to "<strong>" and "</strong>" for description
        And I set highlight tags to "<em>" and "</em>" for nick_name
        When I execute it on the index named "my_index" for type "_doc"
        Then the result with the id 7 should contain "<em>Batman</em>" in "nick_name"
        And the result with the id 7 should contain "I <3 pasta ... and <strong>batman</strong>" in "description"
        And the result with the id 3 should contain "<em>Batman</em>" in "nick_name"

    Scenario: Aggregations
        Given I build a query with aggregation :
            | name          | category  | field     |
            | sum_age       | sum       | age       |
            | genders       | terms     | gender    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result for aggregation "sum_age" should contain 1121
        And the bucket result for aggregation "genders" should contain 4 result for "male"
        And the bucket result for aggregation "genders" should contain 3 result for "female"

    Scenario: Simple Bool Query
        Given I build a must bool query with :
            | field         | value       | condition |
            | age           | 910         | should    |
            | age           | 45          | should    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[3;2]"

    Scenario: Multiple should Bool Query
        Given I build a should bool query with :
            | field             | value         | condition |
            | age               | 910           | should    |
        And I build a should bool query with :
            | field             | value         | condition |
            | age               | 45            | should    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[3;2]"

    Scenario: Multiple must Bool Query
        Given I build a must bool query with :
            | field             | value         | condition |
            | age               | 910           | should    |
            | age               | 45            | should    |
        And I build a must bool query with :
            | field             | value         | condition |
            | first_name        | Diana         | should    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[2]"
    Scenario: Combining MUST and SHOULD inside a Bool Query
        Given I build a should bool query with :
            | field             | value         | condition |
            | age               | 910           | should    |
            | age               | 45            | should    |
            | first_name        | Diana         | must    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[2]"

    Scenario: Combining MUST and SHOULD outside a Bool Query
        Given I build a should bool query with :
            | field             | value         | condition |
            | age               | 910           | should    |
        And I build a should bool query with :
            | field             | value         | condition |
            | age               | 45            | should    |
        And I build a must bool query with :
            | field             | value         | condition |
            | first_name        | Diana         | must    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[2]"

    Scenario: Combining queries and filters inside a Bool Query
        Given I build a should bool query with :
            | field      | value       | condition |
            | gender     | male        | should    |
            | gender     | female      | should    |
            | age        | 910         | filter    |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[2]"

    Scenario: Search City with geodistance
        Given I create geo objects of type "_doc" to index "my_geoindex"
        And I search cities with a coordinate "45.764043,4.835658999999964" at "200" km
        When I execute it on the index named "my_geoindex" for type "_doc"
        Then the result should contain exactly ids "[1;3]"

    Scenario: Search City with geodistance and different unit
        Given I create geo objects of type "_doc" to index "my_geoindex"
        And I search cities with a coordinate "45.764043,4.835658999999964" at "200" m
        When I execute it on the index named "my_geoindex" for type "_doc"
        Then the result should contain exactly ids "[1]"

    Scenario: Add random sort and the result is the same
        Given I build a query with filter :
            | type | field  | value  |
            | term | gender | female |
        When I add a random score with "MyTestSeed" as seed
        And  I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[2;4;6]"

    Scenario: Add a linear function sort
        Given I build a linear decay function with :
            | field  | origin | offset | scale |
            | age    | 35     |  1     |  0.8    |
        When  I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[5;1;7]"

    Scenario: Add a gauss function sort
        Given I build a gauss decay function with :
            | field  | origin | offset | scale |
            | age    | 35     |  1     |  0.8    |
        When  I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[5;1;7;6]"

    Scenario: Exist filter
        Given I build a query with filter :
            | type   | field        |
            | exists | license      |
        When  I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[1;2;3;4;5;6]"

    Scenario: Nested filter differentiate entries
        Given I create nested index and populate it on "nested_index"
        And I build a nested filter on "authors" with filters
            | type | field  | value  |
            | term | authors.first_name | Jack |
            | term | authors.last_name | Lee |
        When  I execute it on the index named "nested_index" for type "_doc"
        Then the result should contain 0 hits

    Scenario: Nested filter works
        Given I create nested index and populate it on "nested_index"
        And I build a nested filter on "authors" with filters
            | type | field  | value  |
            | term | authors.first_name | Jack |
            | term | authors.last_name | Kirby |
        When  I execute it on the index named "nested_index" for type "_doc"
        Then the result should contain exactly ids "[1]"

    Scenario: Best fields don't work on all fields
        Given I build a "should" multi match query with "best_fields" searching "bruce batman", and "AND" operator with these fields
            | field  | boost  |
            | first_name | 1 |
            | nick_name | 1 |
        When  I execute it on the index named "my_index" for type "_doc"
        Then the result should contain 0 hits

    Scenario: Cross fields work on all fields
        Given I build a "should" multi match query with "cross_fields" searching "bruce batman", and "AND" operator with these fields
            | field  | boost  |
            | first_name | 1 |
            | nick_name | 1 |
        When  I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[3]"

    Scenario: Simple Post filter
        Given I build a query with aggregation :
            | name          | category  | field     |
            | genders       | terms     | gender    |
        And I build the query with female post filter
        When I execute it on the index named "my_index" for type "_doc"
        Then the bucket result for aggregation "genders" should contain 4 result for "male"
        And the bucket result for aggregation "genders" should contain 3 result for "female"
        And the result should contain exactly ids "[2;4;6]"

    Scenario: Complex Post filter using a Bool Query
        Given I build a query with aggregation :
            | name          | category  | field     |
            | genders       | terms     | gender    |
        And I build the query with female and over 30 post filter
        When I execute it on the index named "my_index" for type "_doc"
        Then the bucket result for aggregation "genders" should contain 4 result for "male"
        And the bucket result for aggregation "genders" should contain 3 result for "female"
        And the result should contain exactly ids "[2;6]"

    Scenario: Prefix query
        Given I build a prefix query matching :
            | field       | value  | condition |
            | gender      | mal    | must    |
        When I execute it on the index named "my_index" for type "_doc"
        And the result should contain exactly ids "[1;3;5;7]"

    Scenario: Script field
        Given I build a query matching :
            | field       | value  | condition |
            | id          | 1      | must      |
        And I build a script field matching :
            | field       | source  | params    | lang     |
            | double_age   | doc['age'].value * 2     | {}        | painless |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result n° "0" should contain field "double_age" equaling "66"

    Scenario: Script field with param
        Given I build a query matching :
            | field       | value  | condition |
            | id          | 1      | must      |
        And I build a script field matching :
            | field       | source  | params    | lang     |
            | double_age   | doc['age'].value * params.multiplier     | {"multiplier":3}        | painless |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result n° "0" should contain field "double_age" equaling "99"

    Scenario: Script score
        Given I build a query matching :
            | field       | value  | condition |
            | id          | 1      | must      |
        And I build a script score function with :
            | source  | params    | lang     |
            |  doc['age'].value * params.multiplier     | {"multiplier":3}        | painless |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result n° "0" should contain field "_score" equaling "99"

    Scenario: Function score options
        Given I build a query matching :
            | field       | value  | condition |
            | id          | 1      | must      |
        And I build a script score function with :
            | source  | params    | lang     |
            |  doc['age'].value * params.multiplier     | {"multiplier":3}        | painless |
            |  doc['age'].value * params.multiplier     | {"multiplier":5}        | painless |
        And I set the function score options as :
            | scoreMode  | boostMode |
            |  sum       | replace   |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result n° "0" should contain field "_score" equaling "264"
        And I set the function score options as :
            | scoreMode  | boostMode |
            |  avg       | replace   |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result n° "0" should contain field "_score" equaling "132"
        And I set the function score options as :
            | scoreMode  | boostMode |
            |  min       | replace   |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result n° "0" should contain field "_score" equaling "99"

    Scenario: Search City with intersect geoshape
        Given I create geo objects of type "_doc" to index "my_geoindex"
        And I search cities with a relation "intersects" to rhône
        When I execute it on the index named "my_geoindex" for type "_doc"
        Then the result should contain exactly ids "[1]"

    Scenario: Search City with disjoint geoshape
        Given I create geo objects of type "_doc" to index "my_geoindex"
        And I search cities with a relation "disjoint" to rhône
        When I execute it on the index named "my_geoindex" for type "_doc"
        Then the result should contain exactly ids "[2;3]"

    Scenario: After search
        Given I add sorting on :
            | field  | order |
            |  age   | asc  |
            |  id   | asc  |
        And I add search after :
            | sort  |
            |  35   |
            |  5   |
        When I execute it on the index named "my_index" for type "_doc"
        Then the result should contain exactly ids "[6;3;2]"