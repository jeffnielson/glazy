<template>
    <footer class="footer" data-background-color="black">
        <hr/>
        <div class="row">
            <div class="col">
                PATREON SPONSORS
                <br/><a href="https://www.patreon.com/derekau">SUPPORT GLAZY ON PATREON!</a>
            </div>
        </div>
        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <div class="my-2 mx-4">
                    <a href="https://www.ceramicmaterialsworkshop.com/online-classes.html" target="_blank">
                        <img class="sponsor-image" src="/img/sponsors/logo_cmw.png" width="200" height="100" alt="Ceramic Materials Workshop"/>
                    </a>
                </div>
                <div class="my-2 mx-4">
                    <a href="https://www.mightymudmixer.com" target="_blank">
                        <img class="sponsor-image" src="/img/sponsors/logo_mighty.png" width="200" height="100" alt="Might Mud Mixer"/>
                    </a>
                </div>
            </div>
        </div>
        <hr/>
        <nav>
            <ul>
                <li>
                    <a href="/" class="nav-link">
                        Home
                    </a>
                </li>
                <li>
                    <a href="http://help.glazy.org/guide/" class="nav-link">
                        Help
                    </a>
                </li>
                <li>
                    <a href="http://help.glazy.org/support.html#how-to-support-glazy" class="nav-link">
                        Donate
                    </a>
                </li>
                <li>
                    <a href="http://help.glazy.org/about/terms-of-service.html" class="nav-link">
                        Terms of Service
                    </a>
                </li>
                <li>
                    <a href="http://help.glazy.org/about/privacy.html" class="nav-link">
                        Privacy
                    </a>
                </li>
            </ul>
        </nav>
        <div class="copyright">
            ©2015-{{ new Date().getFullYear() }}, <a href="/">Glazy.org</a>
        </div>
    </footer>
</template>

<style>

    .sponsor-image:hover {
        opacity: 0.8;
    }

</style>