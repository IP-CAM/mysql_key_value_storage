Just storage.  Can be used as a storage for sessions.

On the shared hosting, there is usually no Memcache / Redis, etc., sometimes you sometimes need to cache.

Idea:

Use table.  MySQL Storage Key / Value
Engine Tables - Memory or InnoDB (Decree in Designer)
In the case of Memory - work directly with the table (GET / SET).
In the case of InnoDB - in the designer we read all the entries in the array, we work with it, in the destructor we write all the backs in Table.  Inserts are wrapped in transactions.

!!!  In MySQL default limit on the size of the table.  Memory - 16MB !!!
