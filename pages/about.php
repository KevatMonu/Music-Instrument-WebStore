<?php
  $pageTitle = "About Us | K&P Music";
  include('pages/header.php');
?>

<main class="about-us-container">
  <section class="hero-section">
    <h1>About K&P Music</h1>
    <p class="tagline">Passionate About Music Since 2021</p>
  </section>

  <section class="our-story">
    <div class="about-container">
      <h2>Our Story</h2>
      <div class="story-content">
        <div class="story-image">
          <img src="assets/home/image/logo.png" alt="K&P Music Founders" />
        </div>
        <div class="story-text">
          <p> <b>K&P</b> Music was founded in 2021 by Monu Kevat and Harshal Pawar, two passionate musicians who dreamed of creating a space where musicians of all levels could find quality instruments and expert advice.</p>
          <p>Was started as a small  shop and expanded to the online world, bringing the joy of music to a wider audience, has grown from a humble collection of guitars and keyboards into <?php echo date('Y') - 2021; ?> years of serving our community with the finest selection of instruments, accessories, and music education resources. Through passion, dedication, and a commitment to quality, we continue to inspire musicians of all levels, whether they’re beginners picking up their first instrument or professionals perfecting their craft.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="our-values">
        <h2>Our Values</h2>
        <div class="values-grid">
            <div class="value-card ">
                <div class="value-card-front">
                    <i class="fa fa-star"></i>
                    <h3>Quality</h3>
                    <p>We personally select every instrument in our inventory to ensure it meets our high standards of craftsmanship and sound quality.</p>
                </div>
                <div class="value-card-back">
                    <p>Behind our quality commitment is a team of expert musicians and technicians who meticulously inspect and test each instrument. We believe that great music starts with exceptional tools.</p>
                </div>
            </div>

            <div class="value-card">
                <div class="value-card-front">
                    <i class="fa fa-users"></i>
                    <h3>Community</h3>
                    <p>We believe in building a vibrant music community through events, workshops, and supporting local musicians and schools.</p>
                </div>
                <div class="value-card-back">
                    <p>Our community initiatives go beyond music. We create spaces for connection, learning, and mutual support, helping musicians of all backgrounds find their voice and share their passion.</p>
                </div>
            </div>

            <div class="value-card">
                <div class="value-card-front">
                    <i class="fa fa-graduation-cap"></i>
                    <h3>Education</h3>
                    <p>Our in-store lessons and workshops help musicians of all ages develop their skills and deepen their love of music.</p>
                </div>
                <div class="value-card-back">
                    <p>Education is our core mission. We provide personalized learning experiences that adapt to individual skill levels, learning styles, and musical aspirations.</p>
                </div>
            </div>

            <div class="value-card">
                <div class="value-card-front">
                    <i class="fa fa-globe"></i>
                    <h3>Innovation</h3>
                    <p>We constantly explore new technologies, instrument designs, and teaching methods to keep music education and performance cutting-edge.</p>
                </div>
                <div class="value-card-back">
                    <p>Innovation drives us forward. We collaborate with musicians, engineers, and educators to develop new ways of learning, creating, and experiencing music.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="our-team">
    <h2>Meet Our Team</h2>
    <div class="team-members">
        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/home/image/arjun.jpeg" alt="Arjun Bodhan" />
                <h3>Arjun Bodhan</h3>
                <p class="position">Store Manager</p>
            </div>
            <div class="team-member-back">
                <h3>Arjun Bodhan</h3>
                <p class="bio">Arjun has been surrounded by music his entire life. With expertise in brass and woodwind instruments, he leads our team with passion and dedication.</p>
                <div class="contact">
                    <p> <b>Email:</b> arjun@kpmusic.com</p>
                    <p><b>Specialties:</b> Brass & Woodwind</p>
                </div>
            </div>
        </div>

        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/home/image/gaurav.jpeg" alt="Gaurav Wadkar" />
                <h3>Gaurav Wadkar</h3>
                <p class="position">String Specialist</p>
            </div>
            <div class="team-member-back">
                <h3>Gaurav Wadkar</h3>
                <p class="bio">A classically trained violinist with over 15 years of performance experience, Gaurav helps customers find their perfect string instrument, from beginner violins to professional cellos.</p>
                <div class="contact">
                    <p> <b>Email:</b> gaurav@kpmusic.com</p>
                    <p> <b>Specialties:</b>  Violins, Cellos</p>
                </div>
            </div>
        </div>

        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/home/image/Sahil.jpeg" alt="Sahil Kondalkar" />
                <h3>Sahil Kondalkar</h3>
                <p class="position">Guitar Expert</p>
            </div>
            <div class="team-member-back">
                <h3>Sahil Kondalkar</h3>
                <p class="bio">With deep knowledge of electric and acoustic guitars, Sahil has been helping guitarists find their sound at K&P Music for over a decade.</p>
                <div class="contact">
                    <p><b>Email:</b> sahil@kpmusic.com</p>
                    <p><b>Specialties:</b> Electric & Acoustic Guitars</p>
                </div>
            </div>
        </div>

        <div class="team-member">
            <div class="team-member-front">
                <img src="assets/home/image/pravin.jpeg" alt="Parvin Pawar" />
                <h3>Parvin Pawar</h3>
                <p class="position">Music Education Director</p>
            </div>
            <div class="team-member-back">
                <h3>Parvin Pawar</h3>
                <p class="bio">Parvin coordinates our lesson programs and workshops, bringing her experience as a music educator to help students of all ages discover the joy of making music.</p>
                <div class="contact">
                    <p><b>Email:</b> parvin@kpmusic.com</p>
                    <p><b>Specialties:</b> Music Education</p>
                </div>
            </div>
        </div>
    </div>
</section>

  <section class="store-info">
    <h2>Visit Our Store</h2>
    <div class="store-details">
      <div class="map">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3782.0030997589285!2d73.7507627745495!3d18.578303871515914!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bc2ba33f8d3d9e7%3A0xa5f95e0d5a1e2d0b!2sSus%2C%20Pune%2C%20Maharashtra%20411211%2C%20India!5e0!3m2!1sen!2sin!4v1709801000000!5m2!1sen!2sin" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
      <div class="contact-info">
        <p><strong>Address:</strong> Land of Wand, Water Seven , East Blue-07</p>
        <p><strong>Phone:</strong> (+91) 365-987-4210</p>
        <p><strong>Hours:</strong> 24/7</p> 
        <ul>
          <li> <b>Monday - Friday</b> : 10am - 8pm</li>
          <li> <b>Saturday</b> : 9am - 6pm</li>
          <li> <b>Sunday</b> : 11am - 5pm</li>
        </ul>
        <a href="contact.php" class="btn">Contact Us</a>
      </div>
    </div>
  </section>

  <section class="testimonials">
        <h2>What Our Customers Say</h2>
        <div class="testimonial-slider">
            <div class="testimonial">
                <blockquote>"K&P Music has been my go-to music store for over 2 years. Their expertise and friendly service keep me coming back."</blockquote>
                <p class="author">— Abhishek D., Guitarist</p>
            </div>

            <div class="testimonial">
                <blockquote>"The K&P helped my daughter find her first violin and have supported her musical journey ever since. We couldn't ask for better guidance!"</blockquote>
                <p class="author">— Raviraj K., Parent</p>
            </div>

            <div class="testimonial">
                <blockquote>"As a professional musician, I appreciate the quality instruments at K&P. They understand the needs of performers at every level."</blockquote>
                <p class="author">— Shena G., Professional Pianist</p>
            </div>
        </div>
    </section>
</main>

<?php include('pages/footer.php'); // Assuming you have a footer file ?>