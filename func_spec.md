# Params spec #
## Alternative ##
* new : name, experiment_name
    - name
        + 'blue'
        + ['blue'=>23]
    - experiment_name
        + 'color'

## Experiment ##
* new : name, options
    - name
        + 'color'
    - options
        + 'alternatives'
            * ['blue', 'red', 'green']
            * ['blue'=>1,'red'=>5,'green'=>10]
            * ['blue'=>1,'red','green'=>10]
                - to Alternative
        + 'goals'
            * ['a','b']
        + 'metadata'
            * ['blue'=>['text'=>'xxx','size'=>'large'], 'red'=>.....]
        + 'resettable'
            * true or false
        + 'algorithm'
            * XXXX::class

