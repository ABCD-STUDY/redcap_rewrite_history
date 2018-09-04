## Rewrite History

### Description

Allow users to rename an item in a REDCap project including rewriting the history of the data capture for that item - similar to a 'git mv'. 

We currently search/replace the following REDCap locations for references to the item:
- data
- instruments (element names, branching logic, tags, notes, archives)
- reports
- logs
- data quality rules

TODO:
- update snapshot of instruments
- items in review (will not fix)

![Web Interface](/images/snapshot.png "Web Interface")
