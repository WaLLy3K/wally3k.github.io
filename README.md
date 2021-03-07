# WaLLy3K's GitHub pages repo
This content is for my domain https://firebog.net as well as my Big Blocklist Collection.

## Why use this over other sources?
Due to my DNS sink-holing experience (which I've been running on my very active household network since roughly 2013), I've been able to get a good feel for what lists cause issues and which don't. This experience lets me categorise the lists and, more importantly, provide easily accessible recommendations for you to implement into your network.

There's also the fact that there are very few sources of original blocklist content out there. A considerable percentage of lists I've seen is essentially the ["I made this" meme](https://knowyourmeme.com/memes/i-made-this), which leads to the following issues:
  * Cessation: Consolidated lists deprive the original list maintainer of visits.
      * If their visit count falls, it's reasonable to expect one would stop updating the list because their efforts are no longer appreciated
      * Lack of maintenance can lead to even less original blocklist content
  * Centralisation: You're letting one entity essentially dictate what can and can't be blocked
      * This puts a *lot* of workload on a single person to maintain changes to their consolidated list, potentially bringing into question how long a consolidated list will be maintained for, which could become an issue due to the "set and forget" nature of DNS sink-holing
      * The consolidated list maintainer may not always be up-to-date with the original list source
      * Additions and removals may not be passed upstream to the maintainer of that original list, benefitting more people overall   

My goals are to:
  * Credit other maintainer's high-quality content by way of "direct hits" on their content
  * Make that content as easy to access for others as possible
  * Not require payment or to nag for donations
  * Have a transparent changelog thanks to GitHub's commit history

These goals ensure every interested individual or group can have better control over their Internet experience.

On a related note, I have zero interest in maintaining content inside various blocklists, except for the handful of domains I put into [my blocklist](https://v.firebog.net/hosts/static/w3kbl.txt) which have cropped up here and there over the years because it was quicker to add to that list than submit to anyone else.

## Found a false positive, but don't know which list contains it?
Run `pihole -q blockeddomain.com`, and it will return the URL of the block list.

## Know which list contains the false positive?
Click on the big blue "Toggle List Maintainer Sources" button to show the source of a particular blocklist. Via the source page, you should be able to find the contact details for the list maintainer. Get in touch with them to remove the false positive - if you're not able to find the maintainer's contact details, please feel free to reach out to me.

## Unable to find the list maintainer; they're unresponsive or have another issue?
Open a ticket here, and I'll be happy to see what I can do.

I attempt to reply to GitHub tickets and mentions as soon <i>as soon as I notice open tickets</i>, so by all means __please__ ping me <a href="https://firebog.net/about">anywhere else that I frequent</a> that's convenient for you. Also, note that if a ticket is closed, you are welcome to comment on it still if you have any question, comment or concern. :smiley:
