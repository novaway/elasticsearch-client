Feature: Add and remove data to the elasticsearch index

    Scenario: Create index with mapping
        Given there is no index named "my_index"
        When I create an index named "my_index" with the configuration from "data/config.yml"
        Then the index named "my_index" should exist
        And the mapping of the index named "my_index" for the type "my_type" should be :
            | property   | type    | analyzer |
            | age        | integer |          |
            | first_name | text    | standard |
            | nick_name  | text    | standard |

    Scenario: Reload index
        Given there is an index named "my_index"
        And the index named "my_index" is not new
        When I reload the index named "my_index"
        Then the index named "my_index" should exist
        And the index named "my_index" is new

    Scenario: Index object
        Given I reload the index named "my_index"
        When I add objects of type "my_type" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        Then the object of type "my_type" indexed in "my_index" with id "1" has data :
            | first_name | nick_name | age |
            | Barry      | flash     | 33  |

    Scenario: Update object
        Given I reload the index named "my_index"
        And I add objects of type "my_type" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        When I update object of type "my_type" with id "1" in index "my_index" with data :
            | first_name | nick_name | age |
            | Barry      | Savitar   | 35  |
        Then the object of type "my_type" indexed in "my_index" with id "1" has data :
            | first_name | nick_name | age |
            | Barry      | Savitar   | 35  |

    Scenario: Delete object
        Given I reload the index named "my_index"
        And I add objects of type "my_type" to index "my_index" with data :
            | id | first_name | nick_name | age |
            | 1  | Barry      | flash     | 33  |
        When I delete the object with id "1" of type "my_type" indexed in "my_index"
        Then the object of type "my_type" indexed in "my_index" with id "1" does not exist
