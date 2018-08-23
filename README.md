## Rewrite History

### Description

Allow users to rename an item in a REDCap project including rewriting the history of the item - similar to a 'git mv'.

We currently search the following REDCap locations for references to the item:
- instruments (element names, branching logic, tags, notes)
- reports
- logs

TODO:
- update snapshot of instruments
- complex piping with chained (code)

![Web Interface](/images/snapshot.png "Web Interface")
