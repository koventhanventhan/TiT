import unittest
from Player import Player
from Team import Team
from CricketMatch import CricketMatch
from FootballMatch import FootballMatch
from League import League

class TestSimulation(unittest.TestCase):
    def test_cricket_points_sum(self):
        p1 = Player("P1", 20, "Role", 80, 100)
        p2 = Player("P2", 20, "Role", 20, 100)
        t1 = Team("T1", [p1])
        t2 = Team("T2", [p2])
        match = CricketMatch(t1, t2)
        match.distribute_points()
        self.assertAlmostEqual(t1.points + t2.points, 102)

    def test_football_winner_by_goals(self):
        p1 = Player("P1", 20, "Forward", 80, 100, goals=2)
        p2 = Player("P2", 20, "Forward", 20, 100, goals=1)
        t1 = Team("T1", [p1])
        t2 = Team("T2", [p2])
        match = FootballMatch(t1, t2)
        match.play()
        self.assertEqual(match.winner, t1)

    def test_influence_calculation(self):
        p = Player("P", 30, "X", 80, 50)
        self.assertEqual(p.calculate_influence(), 40.0)

if __name__ == "__main__":
    unittest.main()
