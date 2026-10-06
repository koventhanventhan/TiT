import React from 'react'
import { Helmet } from 'react-helmet-async'
import Hero from '../components/Hero'
import Classes from '../components/Classes'
import WhyChooseUs from '../components/WhyChooseUs'
import MobileApp from '../components/MobileApp'
import Onboarding from '../components/Onboarding'
import StudentToolkit from '../components/StudentToolkit'
import StudentsParentsLoveUs from '../components/StudentsParentsLoveUs'

const HomePage = () => {
  return (
    <>
      <Helmet>
        <title>TiT - Online Education Platform</title>
        <meta name="description" content="TiT Online Education Platform offers Sri Lankan Tamil-medium tuition classes for Grade 1-13 with live Zoom classes, recorded lessons, and study materials." />
      </Helmet>
      <div style={{ paddingTop: '7.5rem' }}>
        <Hero />
        <Classes />
        <WhyChooseUs />
        <MobileApp />
        <Onboarding />
        <StudentToolkit />
        <StudentsParentsLoveUs />
      </div>
    </>
  )
}

export default HomePage

