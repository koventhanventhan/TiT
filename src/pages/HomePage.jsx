import React from 'react'
import Hero from '../components/Hero'
import Classes from '../components/Classes'
import WhyChooseUs from '../components/WhyChooseUs'
import MobileApp from '../components/MobileApp'
import Onboarding from '../components/Onboarding'
import StudentToolkit from '../components/StudentToolkit'
import StudentsParentsLoveUs from '../components/StudentsParentsLoveUs'

const HomePage = () => {
  return (
    <div style={{ paddingTop: '120px' }}>
      <Hero />
      <Classes />
      <WhyChooseUs />
      <MobileApp />
      <Onboarding />
      <StudentToolkit />
      <StudentsParentsLoveUs />
    </div>
  )
}

export default HomePage

