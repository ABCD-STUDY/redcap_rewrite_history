## Rewrite History

### Description

Allow users to rename an item in a REDCap project including rewriting the history of the item - similar to a 'git mv'.

We currently search the following REDCap locations for references to the item:
- instruments (element names, branching logic, tags, notes, archives)
- reports
- logs
- data quality rules

TODO:
- update snapshot of instruments
- items in review (will not fix)

![Web Interface](/images/snapshot.png "Web Interface")
