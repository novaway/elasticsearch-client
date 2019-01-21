Feature: Add and remove data to the elasticsearch index

    Scenario: Create index with mapping
        Given there is no index named "my_index"
        When I create an index named "my_index" with the configuration from "data/config_my_index.yml"
        Then the index named "my_index" should exist
        And the mapping of the index named "my_index" for the type "_doc" should be :
            | property   | type    | analyzer |
            | age        | integer |          |
            | first_name | text    | standard |
            | nick_name  | text    | standard |

    Scenario: Reload index
        Given there is an index named "my_index"
        When I add objects of type "_doc" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        And I reload the index named "my_index"
        Then the index named "my_index" should exist
        And the index named "my_index" is new

    Scenario: Index object
        Given I reload the index named "my_index"
        When I add objects of type "_doc" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        Then the object of type "_doc" indexed in "my_index" with id "1" has data :
            | first_name | nick_name | age |
            | Barry      | flash     | 33  |

    Scenario: Update object
        Given I reload the index named "my_index"
        And I add objects of type "_doc" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        When I update object of type "_doc" with id "1" in index "my_index" with data :
            | first_name | nick_name | age |
            | Barry      | Savitar   | 35  |
        Then the object of type "_doc" indexed in "my_index" with id "1" has data :
            | first_name | nick_name | age |
            | Barry      | Savitar   | 35  |

    Scenario: Delete object
        Given I reload the index named "my_index"
        And I add objects of type "_doc" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        When I delete the object with id "1" of type "_doc" indexed in "my_index"
        Then the object of type "_doc" indexed in "my_index" with id "1" does not exist

    @bulk-delete
    Scenario: Delete bulk objects
        Given I reload the index named "my_index"
        And I add objects of type "_doc" to index "my_index" with data :
            | id | first_name | nick_name    | age |
            | 1  | Barry      | flash        | 33  |
            | 2  | Diana      | Wonder Woman | 910 |
            | 3  | Bruce      | Batman       | 45  |
            | 4  | Barbara    | Batgirl      | 27  |
            | 5  | Oliver     | Green Arrow  | 35  |
        When I bulk delete the objects with ids "[1;3;5]" of type "_doc" indexed in "my_index"
        Then the object of type "_doc" indexed in "my_index" with id "1" does not exist
        And the object of type "_doc" indexed in "my_index" with id "2" exists
        And the object of type "_doc" indexed in "my_index" with id "3" does not exist
        And the object of type "_doc" indexed in "my_index" with id "4" exists
        And the object of type "_doc" indexed in "my_index" with id "5" does not exist

    @hotswap
    Scenario: Hotswap
        Given I reload the index named "my_index"
        And I add objects of type "_doc" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        Then the object of type "_doc" indexed in "my_index" with id "1" has data :
            | first_name | nick_name | age |
            | Barry      | flash     | 33  |
        And I hotswap "my_index" to tmp
        When I delete the object with id "1" of type "_doc" indexed in "my_index"
        Then the object of type "_doc" indexed in "my_index" with id "1" has data :
            | first_name | nick_name | age |
            | Barry      | flash     | 33  |
        And I hotswap "my_index" to main
        Then the object of type "_doc" indexed in "my_index" with id "1" does not exist
