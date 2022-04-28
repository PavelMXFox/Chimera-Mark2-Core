## V.0.9.0 RC
### Back
1. The organization of a transparent REST API through the fox\externalCallable interface - allows you to organize access to the necessary entities with minimal costs, but with all the necessary checks.
2. Support for third-party modules with the ability to install multiple instances of the same module (if supported by the module).
3. User authentication — both using built-in tools and using external OAuth sources (currently Gitlab, Gitea, Yandex, VK support is implemented).
4. User authorization — with the help of the built-in access control system based on roles, flexible verification of the user's access rights is performed. The lists of user roles are checked both by the back when making REST API calls, and by the front — for this, after authentication, the list of access rights of the current user is transmitted.
5. The ability to make requests without authorization, if necessary. For example, to implement Webhooks.
6. Embedded database migrations based on the description of the class structure.
7. A base class for performing standard database functions (write, read, search, delete) on the basis of which you can quickly create entities.
8. memcached support for both embedded and custom objects for quick access to frequently used data
9. Configuration storage for each module in the database (with cache) so it is in the environment of the container.
10. Built-in functionality for registering users by invitation with the ability to automatically add to groups both for granting access and for organizing into logical groups ("lists").
11. Built-in functionality of action confirmation codes for both built-in functions (password recovery, mail confirmation, etc.) and for user modules.
12. Support for S3-compatible storage management (creating bouquets, deleting data, etc.).
13. The ability to add any other object storage based on a typical interface.
14. Storing files (email attachments, servicedesk attachments, templates and report results, etc.) in S3-compatible object storage
15. Built-in support for OpenDocument ODS and ODT formats for automated document generation (reports, invoices, acts, etc.)
16. Built-in Fox Converter support for exporting documents in any office format.
17. Built-in support at the base class level of data exchange in JSON format with the ability to control the visibility of individual properties and create virtual properties.
18. Built-in eMail client that supports simultaneous work both for receiving and sending emails, including attachments from multiple accounts using IMAP and SMTP protocols (with and without authorization).
19. Storing metadata of modules (for example, the last synchronization, etc.) in the built-in storage with caching.
20. Support for receiving and generating data in Prometheus format
21. Built-in REST API Client for communication with third-party systems.
22. Built-in stringExportable && stringImportable interfaces for automatic conversion of objects to a string and back (for example, time to unixTime or ISO).
23. Built-in encryption module for critical data (for example, passwords) as well as the formation of hashes based on an individual master password.
24. A system for generating unique identifiers in the format 1000-0000-00 with a checksum for the formation of a register of documents, inventory and other objects. For example, to organize a global search (in future releases) and barcodes.
25. Built-in cron, which allows you to run periodic processes of modules in parallel with a limit on the maximum execution time and with the possibility of blocking the restart until the last process is completed separately for each task.
26. Support for multiple languages, for example, for sending notifications to e-mail or messengers. The list of languages may vary for Chimera Core and additional modules.
27. Logging of all user actions.

### Front
1. Built-in methods of REST interaction with both your own backup and other services.
2. Formation of a visual basic user interface in several languages
3. Basic system administration functions
4. Authorization, user registration. Session monitoring
5. Built-in Fox UI library for creating menus, dialogs, buttons, forms including forms for automatic password generation and autofill.
6. Access rights verification library to control the display of interface elements.
7. The ability to customize the interface by creating your own color themes and images.
8. Using FontAwesome 5 to form interfaces, it is also possible to add your own fonts with icons.