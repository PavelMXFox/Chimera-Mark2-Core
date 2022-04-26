# Static data
if static files exists here - we well get it directly. eg - js or themes.
If not - we will try search ``(css|js|img)`` folder in module ``(module)`` in path
 ``/static/(type)/(module)/(filename)``
 Module struture for static data is:
 
 `` ./css/*`` - for CSS
 
 `` ./img/*`` - for images
 
 `` ./js/*`` - for JavaScripts
 
 `` ./theme/*`` - themes if module provide it
 
 # Themes
 Theme always contains main.css in root
 
 Ant it may contain folders ``css``, ``font`` and ``img`` in folder and in module`s ``theme`` folder